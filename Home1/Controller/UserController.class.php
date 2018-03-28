<?php
namespace Home\Controller;

use Common\Model\UsersModel;
use Common\Model\UserAuthModel;


class UserController extends HomeController
{

    protected function _initialize()
    {
        parent::_initialize();
        if(!$this->user_info['user_id']){
            $this->redirect('Public/login');
        }
        $userInfo = $this->user_info;
        $this->user_id = $userInfo['user_id'];
        $isBindMobile = M('Users') -> where("user_id='$this->user_id'") -> field('mobile') ->find();
        if(empty($isBindMobile['mobile'])){
            $info = array();
            $info['title'] = '绑定手机';
            $agreement = M('Cms_page') -> where("name = '注册协议'") ->find();
            $this->assign('agreement', $agreement);            
            $this->assign('info', $info);
            $this->display('Public/register');
            exit();             
            // $this->redirect('Public/register');
        }          
        $this -> assign('userInfo', $userInfo);
        $this->perfect();
    }
    public function index(){
        $where['user_id'] = $this->user_id;
        $article = M('Cms_article');
        // 从业信息
        $job = M('User_job') -> where($where) -> find();
        // 订阅数
        $subscribe = M('Cms_user_subscribe') -> where("from_user_id='$this->user_id'") ->count();
        // 回答数
        $answer = M('answer_reply')->where(['user_id'=>$this->user_id,'comment_to'=>0])->count();
        // 收藏数
        $collect = M('Cms_article_collect') -> where($where) ->count();
        // 投稿数
        $contribution = $article -> where($where) ->count();
        // Ｈ币数
        $hbCount = M('User_wallet') -> where($where) -> find();
        // 近期提问
        // 近期投稿
        $newContribution = $article -> where($where) -> order('post_time desc') -> LIMIT(0,3) ->select();
        // 推荐问题
        // 推荐阅读
        $recomment = $article  ->where("is_pass=1 ")-> order('post_time desc,is_rec desc') -> LIMIT(0,3)->select();
        //最新活动
        $activity_list = M("Activity")->order('sort asc')->LIMIT(0,4)->select();
        $this->assign('activity_list', $activity_list);
//        var_dump($activity_list);
        // print_r($contribution);
        $info = array('collect' => $collect,
                      'contribution' => $contribution,
                      'subscribe' => $subscribe,
                      'jobinfo' => $job,
                      'answer' => $answer,
                      'hbcount' => number_format($hbCount['money'])
                      );
        $this->assign('recomment', $recomment);
        $this->assign('newContribution', $newContribution);
        $this->assign('myinfo', $info);
        $this->assign('empty_article','<p class="empty">暂无投稿</p>');
        $this-> display();
    }
	public function ask(){
		$A=A('Answer');
		$return=$A->getIndexList(0,2,true);
		$this->page=$return['page'];
		$this->data=$return['data'];
		$this->anser_count=$this->answerCount();
		$this->display();
	}
    public function answer(){
		$A=A('Answer');
		$return=$A->getMyAnswerList(true);
		$this->page=$return['page'];
		$this->data=$return['data'];
		$this->anser_count=$this->answerCount();
        $this->display();
    }
	private function answerCount(){
		$D=D('Answer');
		$R=D('AnswerReply');
		$count['ask']=$D->where(['user_id'=>$this->user_id])->count('id');
		$reply_id=$R->where(['user_id'=>$this->user_id,'comment_to'=>0])->getfield('id',true);
		$count['answer']=count($reply_id);
		$count['useful']=$reply_id?M('answer_reply_useful')->where(['reply_id'=>['in',$reply_id]])->count():0;
		$count['adopted']=$R->where(['user_id'=>$this->user_id,'comment_to'=>0,'adopted'=>1])->count('id');
		return $count;
	}
    public function mycollect($p = 1, $row = 10){
        $articleIdArr = M('Cms_article_collect') -> where("user_id='$this->user_id'") -> select();
        if(empty($articleIdArr)){
            $this->assign('empty','<p class="empty">您没有收藏文章</p>');
            $this->display();
            exit(); 
        }        
        foreach ($articleIdArr as $k => $v) {
            $articleId[] = $v['article_id'];
        }   
        $where['article_id'] = array('in',$articleId);
        $list = M("CmsArticle")->where($where)->page($p, $row)->order('article_id desc')->select();
        $page = new \Common\Util\Page(M('CmsArticle')->where($where)->count(), $row);

        $this->assign('page', $page->show());
        $this->assign('list', $list);   
        $this->display();
    }      
    public function message(){
        $sysMsg = M('Message') -> order('post_time desc')->LIMIT(0,5)->select();        
        $actMsg = M('Activity') -> order('post_time desc')->LIMIT(0,8)->select();
        $info = array();
        if(!empty($sysMsg)){
            foreach ($sysMsg as $k => $v) {
                $info[] = array('title' => $v['content'],'id' => $v['message_id'], 'postTime' => $v['post_time'], 'type'=>'系统消息');
            }            
        }
        if(!empty($actMsg)){
            foreach ($actMsg as $k => $v) {
                $info[] = array('title' => $v['title'],'id' => $v['activity_id'], 'postTime' => $v['post_time'], 'type'=>'活动消息');
            }                
        }
        $this->assign('empty','<p class="empty">暂时没有消息</p>');
        $this->assign('info',$info);
        $this->display();
    }
    public function addr(){
        if(IS_POST){
            $data = I('post.params');
            $addArr = array();
            foreach ($data as $k => $v) {
                $key = $v['name'];
                $addArr[$key] = $v['value'];
            }
            $addArr['user_id'] = $this->user_id;
            $res = M('User_addr') -> add($addArr);
            if($res){
               $this->ajaxReturn(['status'=>1,'info'=>'添加成功']);  
            }else{
                $this->ajaxReturn(['status'=>0,'info'=>'添加失败']);
            }
        }
        $addrList =  M('User_addr') -> where("user_id='$this->user_id'") -> select();
        $this -> assign('list', $addrList);    
        $this -> display();
    }
    public function delAddr(){
        if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法提交']);
        $addr_id = I('addr_id');
        $res = M('User_addr') -> where("user_id='$this->user_id' AND addr_id='$addr_id'") -> delete();
        if($res){
           $this->ajaxReturn(['status'=>1,'info'=>'删除成功']);  
       }else{
           $this->ajaxReturn(['status'=>0,'info'=>'删除失败']);
       }
    }          
    public function myhb($p = 1, $row = 20){
        $hbTotal = M('User_wallet') -> where("user_id='$this->user_id'") -> field('money') ->find();
        $hbLog = M('User_log_wallet') -> where("user_id='$this->user_id'")->page($p, $row)->order("time desc") ->select();
        $page = new \Common\Util\Page(M('User_log_wallet')->where("user_id='$this->user_id'")->count(), $row);
        $hbExp = M('Cms_page') -> where("name = 'H币说明'") ->find();
        $this->assign('hbexp', $hbExp);
        if(empty($hbLog)){
            $this->assign('empty','<tr class="empty_hb"><td></td><td style="color:#999">您暂时没有H币记录</td><td></td></tr>');
            $this->display();
            exit();             
        }
//        $resHbLog =array();
//        foreach ($hbLog as $k => $v) {
//           $remark = strtr($v['remark'],array('，' => ','));
//           $remarkArr = explode(',',$remark);
//           $resHbLog[$k]['exp'] = $remarkArr[0];
//           $resHbLog[$k]['count'] = $remarkArr[1];
//           $resHbLog[$k]['times'] = $v['time'];
//        }
        $allHb = number_format($hbTotal['money']);
        $this->assign('hbtotal', $allHb);
        $this->assign('page', $page->show());
        $this->assign('hbLog', $hbLog);
        $this->display();
    }    
    public function subscribe($p = 1, $row = 10){
        $this->countSub();
        $toUserIdArr = M('Cms_user_subscribe') -> where("from_user_id='$this->user_id'") -> select();
        if(empty($toUserIdArr)){
            $this->assign('empty','<p class="empty">您没有订阅成员</p>');
            $this->display();
            exit(); 
        }
        foreach ($toUserIdArr as $k => $v) {
            $toUserId[] = $v['to_user_id'];
        }

        $where['user_id'] = array('in',$toUserId);
        $list = M("CmsArticle")->where($where)->page($p, $row)->order('article_id desc')->select();
        $page = new \Common\Util\Page(M('CmsArticle')->where($where)->count(), $row);

        $this->assign('page', $page->show());
        $this->assign('list', $list);   
        $this->assign('touserid', $toUserIdArr);               
        $this->display();
    }
    public function subpeople($p = 1, $row = 10){
        $this->countSub();
        $toUserIdArr = M('Cms_user_subscribe') -> where("from_user_id='$this->user_id'")->page($p, $row) -> field('to_user_id')-> select();
        $page = new \Common\Util\Page(M('Cms_user_subscribe')->where("from_user_id='$this->user_id'")->count(), $row);
        $this->assign('page', $page->show());
        $this->assign('toUserIdArr', $toUserIdArr);
        $this->display();
    }
    public function mysubsrcibe($p = 1, $row = 10){
        $this->countSub();
        $toUserIdArr = M('Cms_user_subscribe') -> where("to_user_id='$this->user_id'")->page($p, $row) -> field('from_user_id,to_user_id')-> select();
        $page = new \Common\Util\Page(M('Cms_user_subscribe')->where("to_user_id='$this->user_id'")->count(), $row);
        $this->assign('page', $page->show());
        $this->assign('toUserIdArr', $toUserIdArr);
        $this->display();
    }
    public function manager(){
        $where['user_id'] = $this->user_id; 
        $job = M('User_job') -> where($where) ->find();
        $auth = M('User_auth') -> where($where) ->find();
        $addr = $job['address'];
        if(!empty($addr)){
            $addrArr = explode(' ',$addr);
            $job['province'] = $addrArr[0];
            $job['city'] = $addrArr[1];
            $job['district'] = $addrArr[2];
        }
        // 验证是否存在登录密码
        $isPsw = M('Users') -> where($where) -> field('password') ->find();
        if(empty($isPsw['password'])){
            $this -> assign('ispsw', 0);
        }
        $this -> assign('userJob', $job);
        $this -> assign('userAuth', $auth);
        $this->display();
    }
    public function saveBasic(){
        if(!IS_AJAX)$this->ajaxReturn(['status'=>0,'msg'=>'非法提交']);
        $data = I('post.');
        if($_FILES['file']['error']==0){
            $Media=D('SystemMedia');
            $file=$Media->singleUpload($_FILES['file']);
            if($file){
                $data['avatar']=$file['url'];
            }else{
                $this->error($Media->geterror());
            }
        }


        $res = M('Users') -> where("user_id=$this->user_id") -> save($data);

        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'修改成功！']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'未做修改！']);
        }
    }
    public function saveWork(){
        if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法提交']);
        $data = I('post.');
        $saveData['address'] = $data['province'].' '.$data['city'].' '.$data['district'];
        $saveData['hospital'] = $data['hospital'];
        $saveData['department'] = $data['department'];
        $saveData['title'] = $data['title'];
        $res = M('User_job') -> where("user_id=$this->user_id") -> save($saveData);
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'修改成功！']);
        }
    }
    public function saveJob(){
        if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法提交']);
        $data = I('post.');
        if($_FILES['licenseImgId']['error']==0){
           
            $Media=D('SystemMedia');
            $file=$Media->singleUpload($_FILES['licenseImgId']);
            if($file){
                $saveData['job_id_photo_media_id']=$file['id'];
            }else{
                $this->error($Media->geterror());
            }
        }

        
        
        $saveData['real_name'] = $data['realName'];
        $saveData['id_card'] = $data['userCard'];

        $saveData['is_auth'] = 1;
        
      
        
        $UserAuthModel = new UserAuthModel();
        $result = $UserAuthModel->update($saveData, $this->user_id);

        if(!$result){
            $this->ajaxReturn(['status'=>0,'msg'=>$UserAuthModel->getError()]);
        }else{
            $isAuth['is_auth'] = 1;
            M('Users') -> where("user_id='$this->user_id'") ->save($isAuth);

            $this->ajaxReturn(['status'=>1,'msg'=>'修改成功！']);
        }

        // $res = M('User_auth') -> where("user_id=$this->user_id") -> save($saveData);
        // if($res){
        //     $this->ajaxReturn(['status'=>1,'msg'=>'修改成功！']);
        // }
    }
    public function verifyPhoneNum(){
        if(IS_POST){
            $mobile      = I('phoneNum');
            $mobileNew    = I('mobileNew');
            if($mobile == $mobileNew){
                $this->ajaxReturn(['status'=>0,'info'=>'新旧手机号不能相同']);
            }
            $User = M('Users');
            $num = $User -> where("user_id='$this->user_id'") -> field('mobile') -> find();
            if($mobile != $num['mobile']){
                $this->ajaxReturn(['status'=>0,'info'=>'旧手机号码错误']);
            }
            $isEx = $User -> where("mobile='$mobileNew'") -> field('mobile') -> find();
            if(!empty($isEx['mobile'])){
                $this->ajaxReturn(['status'=>0,'info'=>'新的手机号已经被注册了']);
            }            
            $this->ajaxReturn(['status'=>1,'info'=>'可以注册']);
        }         
    } 
    public function editMobile(){
        if(IS_POST){
            $mobile      = I('mobile');
            $sms_code    = I('sms_code');            
            if(!check_sms_code($mobile, $sms_code)){
                $this->ajaxReturn(['status'=>0,'msg'=>'请输入正确的验证码']);
            }
            $data['mobile'] = $mobile;
            $res = M('Users') -> where("user_id='$this->user_id'") -> save($data);
            if($res){
                $this->ajaxReturn(['status'=>1,'msg'=>'修改成功！']);
            }
        }  
    }
    public function editPsw(){
        if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法提交']);
        $data = I('post.');
        $isPsw = $data['isPsw'];
        $oldpsw = $data['oldpsw'];
        $newpsw = $data['newpsw'];
        $surepsw = $data['surepsw'];
        if($newpsw != $surepsw){
           $this->ajaxReturn(['status'=>0,'msg'=>'两次密码不相同']); 
        }
        $M = M('Users');
        $isPsw = $M -> where("user_id='$this->user_id'") -> field('password') ->find();
        if(!empty($isPsw['password'])){
            $oldpsw = trim($oldpsw);
            if(!password_verify($oldpsw, $isPsw['password'])){
                $this->ajaxReturn(['status'=>0,'msg'=>'当前密码输入错误']); 
            }
        }
        $saveData['password'] = password_hash($newpsw,PASSWORD_DEFAULT);
        $res = $M -> where("user_id='$this->user_id'") -> save($saveData);
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'修改成功！']);
        }

    }    
    public function countSub(){
        // 订阅数
        $from_user_id = M('Cms_user_subscribe') -> where("from_user_id='$this->user_id'") ->count();
        // 粉丝数
        $to_user_id = M('Cms_user_subscribe') -> where("to_user_id='$this->user_id'") ->count();

        $this -> assign('from_user_id', $from_user_id);
        $this -> assign('to_user_id', $to_user_id);
    }
    //    ajax取消订阅
    public function subscibe(){
        $uid=I('post.to_user_id');
        if(IS_AJAX && $uid){
            $user_id=$this->user_id;
            if($user_id){
                    $ret=D('CmsUserSubscribe')->unsubscribe($uid,$user_id);
                    if($ret){
                        $data['status']=200;
                        $data['msg']="取消订阅成功";
                    }else{
                        $data['status']=400;
                        $data['msg']="取消失败，请稍后再试";
                }
            }else{
                $data['status']=400;
                $data['msg']="请先登录";
            }
        }else{
            $data['status']=400;
            $data['msg']="请使用正确的方式";
        }
        $this->ajaxReturn($data);
    }

    //    ajax订阅我的粉丝
    public function mysubscibe(){
        $id=I('post.id');
        $from_user_id=I('post.from_user_id');
        if(IS_AJAX && $from_user_id){
            $user_id=$this->user_id;
            if($user_id){
                if($id==0){
                    $ret=D('CmsUserSubscribe')->subscribe($from_user_id,$user_id);
                    if($ret){
                        $data['status']=200;
                        $data['msg']="订阅成功";
                    }else{
                        $data['status']=400;
                        $data['msg']="订阅失败，请稍后再试";
                    }
                }else{
                    $ret=D('CmsUserSubscribe')->unsubscribe($from_user_id,$user_id);
                    if($ret){
                        $data['status']=200;
                        $data['msg']="取消订阅成功";
                    }else{
                        $data['status']=400;
                        $data['msg']="取消失败，请稍后再试";
                    }
                }

            }else{
                $data['status']=400;
                $data['msg']="请先登录";
            }
        }else{
            $data['status']=400;
            $data['msg']="请使用正确的方式";
        }
        $this->ajaxReturn($data);
    }

//    资料完善度
public function perfect(){
    $where['user_id']=$this->user_id;
    $users=M('users')->field('nickname,avatar,sex,birthday,signature')->where($where)->find();
    $userJob=M('userJob')->field('address,hospital,department,title')->where($where)->find();
    $userAuth=M('userAuth')->field('real_name,id_card,job_id_photo_media_id')->where($where)->find();
    $all=array_merge($users,$userJob,$userAuth);
    $score=floor((count(array_filter($all))/count($all))*100)."%";
    $this -> assign('score', $score);

}

}