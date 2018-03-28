<?php
namespace Home\Controller;

use Common\Model\UsersModel;
use Common\Controller\WechatController;

class HomeController extends WechatController
{
    public $user_id;
    public $user_info;

//    检测登录
    protected function _initialize()
    {
        if(isMobile()){
            $this->redirect('Mobile/index');
        }
        $UsersModel = new UsersModel();
        if(UsersModel::isLogin()){
            $user_id   = UsersModel::isLogin();
            $user_info = $UsersModel->find($user_id);
            if(!$user_info){
                UsersModel::logout();
            }
        }
        // $this->user_id   = UsersModel::isLogin();
        $this->user_id   = session('user_id')?:0;
//        $this->user_id  =16;
        $this->user_info = $UsersModel->getInfoByUid($this->user_id);
        $this->assign('userInfo',$this->user_info);

    }


}