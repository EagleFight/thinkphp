<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/19
 * Time: 9:02
 */
namespace Home\Controller;
use Common\Model\UsersModel;
use Think\Controller;
class OtherController extends HomeController
{

    public function auto()
    {

        $uid=I("get.uid");
        if(!$uid){
            $this -> display("Public/404");
            exit();
        }

        $userInfo=$this->user_info;
        $this->assign('userInfo',$userInfo);
        $user_id=$this->user_id;
        $isSubscribe = M('Cms_user_subscribe') -> where("to_user_id='$uid' and from_user_id='$user_id'") ->find();
        $isSubscribe=empty($isSubscribe)?'0':'1';
        $this->assign('isSubscribe', $isSubscribe);


        $user= M('Users')->field('user_id,nickname,avatar,is_auth,signature')->where("user_id=$uid ")->find();
        if(empty($user['user_id'])){
            $this -> display("Public/404");
            exit();            
        }        
        //$this->assign('user', $user);
		$this->user=$user;

        // 收藏数
        $collect = M('Cms_article_collect') -> where("user_id=$uid") ->count();
        // 投稿数
        $tougao = M('Cms_article') -> where("user_id=$uid and is_pass='1'") ->count();
        // 订阅数
        $dingyue =M('Cms_user_subscribe') -> where("from_user_id='$uid'") ->count();
        $this->assign('collect', $collect);
		$this->assign('answer_count',M('answer_reply')->where(['user_id'=>$uid,'comment_to'=>0])->count());
        $this->assign('tougao', $tougao);
        $this->assign('dingyue', $dingyue);
    }


    public function index(){
        $uid=I("get.uid");
        $where['user_id'] = $uid;

        $this->auto();


        $article = M('Cms_article');
        // 从业信息
        $job = M('User_job') -> where($where) -> find();
        // 粉丝
        $subscribe = M('Cms_user_subscribe') -> where("to_user_id='$uid'") ->count();



        // 回答数
        //$answer = M('answer_reply')->where(['user_id'=>$uid,'comment_to'=>0])->count();

        //阅读量
        $countArticle = $article->field('view_num,like_num') -> where($where) ->select();
        $view_num=0;
        $like_num=0;
        foreach ($countArticle as $key=>$val){
           $view_num+=$val['view_num'];
            $like_num+=$val['like_num'];
        }

        // 近期投稿
        $newContribution = $article -> where("user_id=$uid and is_pass='1'") -> order('post_time desc') -> LIMIT(0,3) ->select();


        // 推荐阅读
        $reComment = $article ->where("is_pass = 1") -> order('post_time desc,is_rec desc') -> LIMIT(0,3)->select();


        $info = array(
            'subscribe' => $subscribe,
            'view_num'=>$view_num,
            'like_num'=>$like_num,
            //'answer' => $answer,


        );
        $this->assign('jobinfo', $job);
        $this->assign('recomment', $reComment);
        $this->assign('newContribution', $newContribution);
        $this->assign('myinfo', $info);

        $this-> display();
    }


//收藏
    public function comment($p = 1, $row = 10){
        $this->auto();
        $uid=I('get.uid');
        $list = M('CmsArticle as art ')->join("RIGHT JOIN cms_article_collect as ac ON art.article_id = ac.article_id" )->where("ac.user_id=$uid and art.is_pass='1' ")
            ->page($p, $row) ->field("art.*")->order("art.article_id desc")->select();
        $page = new \Common\Util\Page( M('CmsArticleCollect')->where("user_id=$uid")->count(), $row);
        $this->assign('list', $list);
        $this->assign('page', $page->show());
        $this-> display();
    }
//回答
    public function answer(){
		$this->auto();
		$A=A('Answer');
		$A->user_id=$this->user['user_id'];
		$return=$A->getMyAnswerList(true);
		$this->page=$return['page'];
		$this->data=$return['data'];
        $this->display();

    }
    
//投稿
    public function article(){
        $this->auto();
        $uid=I('get.uid');
        $page=I('get.p');
        $list=D('CmsArticleCollect')->article($uid,$page);
        $this->assign('list', $list['list']);
        $this->assign('page',$list['show']);// 赋值分页输出
        $this-> display();
    }
//订阅
    public function subscribe($p = 1, $row = 10){
        $this->auto();
        $user_id=$this->user_id;
        $uid=I('get.uid');
        $list=M('CmsUserSubscribe')->where("from_user_id='$uid'")  ->page($p, $row)->select();
        $page = new \Common\Util\Page( M('CmsUserSubscribe')->where("from_user_id=$uid")->count(), $row);
        $this->assign('list', $list);
        $this->assign('page', $page->show());

        $this->assign('user_id', $user_id);
        $this-> display();
    }

//    ajax订阅
public function subscibe(){
    $uid=I('post.uid');
    $status=I('post.status');
    if(IS_AJAX && $uid){
        $user_id=$this->user_id;
        if($user_id){
            if($status==0){
                $ret=D('CmsUserSubscribe')->subscribe($uid,$user_id);
                if($ret){
                    $data['status']=200;
                    $data['msg']="订阅成功";
                }else{
                    $data['status']=400;
                    $data['msg']="订阅失败，请稍后再试";
                }
            }else{
                $ret=D('CmsUserSubscribe')->unsubscribe($uid,$user_id);
                if($ret){
                    $data['status']=200;
                    $data['msg']="取消订阅成功";
                }else{
                    $data['status']=400;
                    $data['msg']="取消失败，请稍后再试";
                }
            }

        }else{
            $data['status']=600;
            $data['msg']="请先登录";
        }
    }else{
        $data['status']=400;
        $data['msg']="请使用正确的方式";
    }
    $this->ajaxReturn($data);
}




    public function reward(){
        $num=abs((int)I("post.number"));
        $infoId=I("post.artId");
        $info=M('users')->field('user_id,nickname')->where("user_id=$infoId")->find();
        if($this->user_id){
            $data= D('UserWallet')->reward($num,$this->user_id,$info);
        }else{
            $data="请重新登录";
        }
        $this->ajaxReturn($data);

    }
    public function login(){
        if($this->user_id){
            $data=200;
        }else{
            $data=400;
        }
        $this->ajaxReturn($data);
    }










}
