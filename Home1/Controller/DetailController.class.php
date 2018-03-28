<?php
namespace Home\Controller;
use Common\Model\CmsArticleLikeModel;
use Think\Controller;
use Common\Model\CmsCommentModel;
use Common\Model\UserWalletModel;
use Common\Util\Tree;

class DetailController extends HomeController{
    public function index(){
    	$get = I('get.');
//    	$articleType = $get['article'];
    	$articleId = $get['id'];
    	$Article = M('Cms_article');
    	$info = $Article -> where(" article_id='$articleId'") -> find();
        if(!$info || $info['is_pass']==2){
            $this -> display("Public/404");
            exit();
        }
        if($info['is_pass']==0){
            if($info['user_id']==$this->user_id){
                $this -> assign('preview',1);
            }else{
                $this -> display("Public/404");
                exit();
            }
        }else{
            // 评论
            $CmsCommentModel = new CmsCommentModel();
            $msgList = $CmsCommentModel -> where("article_id='$articleId'") -> order('post_time desc, like_num desc, comment_id asc') ->select();
            $Tree = new Tree();
            $comment_tree = $Tree->list_to_tree($msgList, 'comment_id', 'parent_comment_id');

            //检查关注
            $info['isSubscribe']= $this->is_subscribe($info['user_id']);
            //是否点赞
            $info['is_like']=$this->is_like($info['article_id']);
            //是否收藏
            $info['is_collect']=$this->is_collect($info['article_id']);

            //文章阅读
            $this->article_read();
        }


        // 最新资讯
        $newArticle = M("Cms_article")->where('is_pass="1" ') -> order('post_time desc') -> LIMIT(0,3)->select();

        // 最热1周
        $hotArticle= $this->hotArt(30);

        // 相关推荐
        $recommendArticleWhere['category_id']=$info['category_id'];
        $recommendArticleWhere['is_pass']="1";
        $recommendArticle = M("Cms_article")->where($recommendArticleWhere) -> order('post_time desc,is_rec desc') -> LIMIT(0,4)->select();




        $this -> assign('info',$info);
        $this -> assign('getID',$get['id']);
        $this -> assign('userId',$this->user_id);
    	$this -> assign('comment',$comment_tree);
        $this -> assign('hotArticle',$hotArticle);
        $this -> assign('newInformation',$newArticle);
        $this -> assign('recommend',$recommendArticle);
        $this -> display();
        // $category=D("CmsCategory");
        // $article_list=$category->article_list($articleType,$p = 1, $row = 2);
    }
public function hotArt($d){
    $day=24*60*60*7*$d;
    $time=date("Y-m-d",time()-$day);
    $hotArticle = M("Cms_article")->where("is_pass='1' and post_time>'$time' ") -> order('view_num desc') -> LIMIT(0,5)->select();
    return $hotArticle;
}
//    是否已关注
public function is_subscribe($to_user_id){
    $from_user_id=$this->user_id;
    if($from_user_id){
       return D('CmsUserSubscribe')->isSubscribe($to_user_id,$from_user_id);
    }else{
        return 0;
    }

}


    //    是否已点赞
    public function is_like($article_id){
        if($this->user_id){
         return  D('CmsArticleLike')->isLike($article_id,$this->user_id);
        }else{
            return 0;
        }
    }

//    是否收藏
    public function is_collect($article_id){
        if($this->user_id){
            return  D('CmsArticleCollect')->isCollect($article_id,$this->user_id);
        }else{
            return 0;
        }
    }

    // 点赞
    public function like(){
        $artID=I('post.artId');
        $is_like=I('post.is_like');
        if(!$this->user_id && !$_SESSION['visitor_like']){
            $visitor_like=date("mdHs",time()).rand(1,99);
            $_SESSION['visitor_like']=$visitor_like;
        }


        $user_id=empty($this->user_id)?$_SESSION['visitor_like']:$this->user_id;

        if($artID){
            if($is_like==1){
                if(D('CmsArticleLike')->unlike($artID,$user_id)){
                    $data['status']=200;
                    $data['msg']="取消点赞";
                }else{
                    $data['status']=400;
                    $data['msg']="取消失败";
                }
            }elseif($is_like==0){
//                以前未点赞。现在点赞
                if(D('CmsArticleLike')->like($artID,$user_id)){
                    $data['status']=200;
                    $data['msg']="文章点赞成功";
                }else{
                    $data['status']=400;
                    $data['msg']="文章点赞失败";
                }
            }

        }else{
            $data['status']=400;
            $data['msg']="请稍后再试";
        }
        $this->ajaxReturn($data);
    }

    // 收藏
    public function collect(){
        $artID=I('post.artId');
        $is_collect=I('post.is_collect');
        if($this->user_id && $artID){
            if($is_collect==1){
                if(D('CmsArticleCollect')->uncollect($artID,$this->user_id)){
                    $data['status']=200;
                    $data['msg']="取消点赞";
                }else{
                    $data['status']=400;
                    $data['msg']="取消失败";
                }
            }elseif($is_collect==0){
//                以前未点赞。现在点赞
                if(D('CmsArticleCollect')->collect($artID,$this->user_id)){
                    $data['status']=200;
                    $data['msg']="文章点赞成功";
                }else{
                    $data['status']=400;
                    $data['msg']="文章点赞失败";
                }
            }

        }else{
            $data['status']=600;
            $data['msg']="请先登录";
        }
        $this->ajaxReturn($data);
    }


    public function comment(){
        if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法提交']);
        $data = I('post.');       
        // if(empty($saveData['user_id']))$this->ajaxReturn(['status'=>0,'msg'=>'请先登录']);
        if(empty($this->user_id)){
            $saveData['visitor'] = '游客'.time();
            $saveData['user_id'] = 0;
        }else{
            $saveData['user_id'] = $this->user_id; 
        }
        $saveData['article_id'] = $data['artId'];
        $saveData['content'] = $data['text'];
        $saveData['post_time'] = date("Y-m-d H:i:s", time());
        $saveData['parent_comment_id'] = empty($data['comId'])?'0':$data['comId'];
        if(!empty($this->user_id)){
            $article_id = $data['artId'];
            $user_id = $this->user_id;
            $UserWalletModel = new UserWalletModel();
            $UserWalletModel->commentArticle($article_id, $user_id);
        }       
        $res = M('Cms_comment') -> add($saveData);
        $saveData['comment_id'] = $res;
        $this->assign('msglist',$saveData);
        if(empty($data['comId'])){
            $content = $this->fetch('msglist');
            $this->ajaxReturn(['status'=>1,'msg'=>$content]);
        }else{
            if($data['stat'] == 2){
               $content = $this->fetch('report2'); 
               $this->ajaxReturn(['status'=>2,'msg'=>$content]);
            }else{
               $content = $this->fetch('report3');
               $this->ajaxReturn(['status'=>3,'msg'=>$content]);  
            }
        }
    }

    public  function article_read(){
        $user_id=$this->user_id;
        $article_id=I("get.id");
        if($article_id){
           if(!$user_id){
               $user_id = 0; 
           }            
           $data= D("ArticleLog")->article_read($user_id,$article_id);
           return $data;
        }
    }

    public function share(){
        if(IS_AJAX){
            $shareId=I('post.shareId');
            $artID=I('post.artId');
            $user_id=$this->user_id?$this->user_id:0;
            if ($shareId && $artID){
                $content=D("ArticleLog")->share($shareId,$artID,$user_id);
                 $this->ajaxReturn(['status'=>3,'msg'=>$content]);
            }else{
                 $this->ajaxReturn(['status'=>2,'msg'=>"参数错误"]);
            }
        }else{
             $this->ajaxReturn(['status'=>1,'msg'=>"请使用正确的方式"]);
        }
    }

public function attention(){
    $artID=I('post.artId');
    if(IS_AJAX && $artID){
        $user_id=$this->user_id;
        if($user_id){
            $Article = M('Cms_article');
            $info = $Article->field('user_id') -> where(" article_id='$artID' ") -> find();
            $ret=D('CmsUserSubscribe')->subscribe($info['user_id'],$user_id);
            if($ret){
                $data['status']=200;
                $data['msg']="关注成功";
            }else{
                $data['status']=400;
                $data['msg']="关注失败，请稍后再试";
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
    $artId=I("post.artId");
    $info = M("CmsArticle") -> where(" article_id='$artId' ") -> find();
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