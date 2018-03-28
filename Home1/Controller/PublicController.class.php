<?php
namespace Home\Controller;

use Common\Model\UsersModel;
// use Common\Model\UserOauthModel;
// use Common\Model\UserAuthModel;
use Common\Model\CmsPageModel;

class PublicController extends HomeController
{
    public function register($return_url = '')
    {
        if(IS_POST){
            $mobile      = I('mobile');
            $sms_code    = I('sms_code');
            $getData = I('post.');
            $data['mobile'] = $getData['mobile'];
            $data['password'] = $getData['psw'];
            $data['avatar'] = '/Public/pc/images/default.jpg';
            $data['nickname'] = '用户'.time();
            if($data['password'] != $getData['surepsw']){
                $this->ajaxReturn(['status'=>0,'info'=>'两次密码不相同']);
            }
            if(!check_sms_code($mobile, $sms_code)){
                $this->ajaxReturn(['status'=>0,'info'=>'请输入正确的验证码']);
            }
            
            $UsersModel     = new UsersModel();
            // $UserAuthModel  = new UserAuthModel();
            // $UserOauthModel = new UserOauthModel();
            
            // 检查手机是否被绑定
            $where = array();
            $where['mobile'] = $mobile;

            $user_info = $UsersModel->where($where)->find();
            // (1)手机号未绑定用户,直接绑定
            if(!$user_info){
                $userId = session('user_id');
                if(!empty($userId)){
                    $saveData['mobile'] = $mobile;
                    $psw = password_hash($data['password'],PASSWORD_DEFAULT);
                    $saveData['password'] = $psw;
                    $res = M('Users') -> where("user_id='$userId'") -> save($saveData);
                    if($res){
                      $this->ajaxReturn(['status'=>1,'info'=>'恭喜你绑定成功，快去个人中心完善资料吧！']);  
                    }
                }else{
                    $result = $UsersModel->addUser($data);
                    if(!$result){
                        $this->ajaxReturn(['status'=>0,'info'=>'注册失败']);
                    }
                    session('user_id',$result);           
                    $this->ajaxReturn(['status'=>1,'info'=>'恭喜你成功注册，快去个人中心完善资料吧！','res'=>$result]);
                }

            }else{
                $this->ajaxReturn(['status'=>0,'info'=>'该手机号已注册，请直接登录']);
            }
            
        }else{
            if(empty(session('user_id'))){
              $this->display('register_1');
              exit();  
            }
            $userId = session('user_id');
            $isBindMobile = M('Users') -> where("user_id='$userId'") -> field('mobile') ->find();
            if(empty($isBindMobile['mobile'])){
                $info = array();
                $info['title'] = '绑定手机';
                $agreement = M('Cms_page') -> where("name = '注册协议'") ->find();
                $this->assign('agreement', $agreement);            
                $this->assign('info', $info);
                $this->display();
                exit();            
                // $this->redirect('Public/register');
            }
            $this->redirect('User/index');
        }
    }
    
    public function registerProtocol()
    {
        $where = array();
        $where['name'] = '注册协议';
        
        $CmsPageModel = new CmsPageModel();
        $info = $CmsPageModel->where($where)->find();
        
        $this->assign('meta_title', '注册协议');
        $this->assign('info', $info);
        $this->display();
    }
    
    public function inviter($inviter = 0)
    {
        // 绑定关系
        set_inviter($this->user_id, $inviter);
        
        // 跳转关注文章
        $url = 'http://mp.weixin.qq.com/s/0tmQNwb7xJL_MVDoHUzVag';
        redirect($url);
    }
    public function login()
    {
        if(IS_POST){
//            $mobile      = I('mobile');
//            if(empty($mobile)){
//                $this->error('手机号不能为空');
//            }
//           $sms_code    = I('sms_code');
//            if(!check_sms_code($mobile, $sms_code)){
//                $this->error('请输入正确的验证码');
//            }
            $mobile    = I('mobile');   
            $getData = I('post.');
            $psw = $getData['psw'];

            $UsersModel = new UsersModel();
            $login=$UsersModel->passwordLogin($mobile,$psw);
            if($login){
                $UsersModel->autoLogin($login);
                session('user_id',$login['user_id']);
                $this->ajaxReturn(['status'=>1,'info'=>'登录成功']);
            }else{
               $this->ajaxReturn(['status'=>0,'info'=>'账号或密码有误']); 
            }

        }else{
            if(!empty(session('user_id'))){
              $this->redirect('User/index');
              exit();
            }
            $info['title'] = '用户登录';
			$dir_refer=I('server.HTTP_REFERER');
			$dir_refer&&$this->refer=$dir_refer;
            $this->assign('info', $info);
            $this->display();
        }
    }
    public function retrieve(){
        if(IS_POST){
            $mobile      = I('mobile');
            $sms_code    = I('sms_code');            
            if(!check_sms_code($mobile, $sms_code)){
                $this->ajaxReturn(['status'=>0,'info'=>'请输入正确的验证码']);
            }
            session('edit_mobile_psw',$mobile);
            $this->ajaxReturn(['status'=>1,'info'=>'验证成功']);
        }else{
            if(!empty(session('user_id'))){
              $this->redirect('User/index');
              exit();  
            }
            $info['title'] = '找回密码';
            $this->assign('info', $info);
            $this->display();            
        }
    }
    public function setpsw(){
        if(!empty(session('edit_mobile_psw'))){
            if(IS_POST){
                $data = I('post.');
                $psw = $data['psw'];
                $mobile = session('edit_mobile_psw');
                if($psw != $data['surepsw']){
                    $this->ajaxReturn(['status'=>0,'info'=>'两次密码不相同']);
                }
                $saveData['password'] = password_hash($psw,PASSWORD_DEFAULT);
                $res = M('Users') -> where("mobile='$mobile'") -> save($saveData);
                if($res){
                    session('edit_mobile_psw',null);
                    $this->ajaxReturn(['status'=>1,'info'=>'修改成功！']);
                }
                $this->ajaxReturn(['status'=>0,'info'=>'修改失败！']);                             
            }else{
                $info['title'] = '找回密码';
                $this->assign('info', $info);
                $this->display(); 
                exit();                
            }  
        }
        $this -> display('Public/404');
    }    
    public function logout(){
        // $UsersModel  = new UsersModel();
        // $UsersModel ->logout();
        session('user_id', null);
        // session('user_auth_sign', null);
        // cookie('user_auth', null);
        // cookie('user_auth_sign', null);

        $this->redirect('index/index');
    }
   
    public function getWxInfo(){
        $code = $_GET["code"];
        $thisUrl = $_GET["thisUrl"];
             
        //取得网页授权的 access_token 和 openid
        $oauth2Url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".WX_APPID."&secret=".WX_SECRET."&code=$code&grant_type=authorization_code";
        $oauth2 = $this -> getJson($oauth2Url);
        $access_token = $oauth2["access_token"]; 
        $openid = $oauth2['openid'];

        //根据全局access_token和openid查询用户信息 
        $get_user_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $userinfo = $this -> getJson($get_user_info_url);
         
        //打印用户信息
        $this -> isWxRegist($userinfo,$thisUrl);
    }
    public function isWxRegist($userinfo,$thisUrl){
        $unionid = $userinfo['unionid'];
        $M = M('Users');
        $regist = $M -> where("unionid='$unionid'") -> field('user_id,mobile,unionid') ->find();
        if(!empty($regist['unionid'])){
            // 微信已经绑定的用户
            session('user_id',$regist['user_id']);
        }else{
            // 新用户，先绑定微信信息
            $UsersModel     = new UsersModel();
            $result = $UsersModel->addUserByWechat($userinfo);
            if($result){
              session('user_id',$result);  
            }
        }
        $thisUrl = urldecode($thisUrl);
        $thisUrl = str_replace(array('.html'),'',$thisUrl);
		redirect($thisUrl);
        //$this->redirect($thisUrl);
    }
    public function getJson($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }

    public function protocol(){
        $this->display();
    }

    public function about(){
        $where = array();
        $where['name'] = '关于我们';

        $CmsPageModel = new CmsPageModel();
        $info = $CmsPageModel->where($where)->find();


        $this->assign('list',$info);
        $this->display();
    }
}