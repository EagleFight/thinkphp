<?php
namespace Admin\Controller;

class UserController extends Controller {
    public function index(){
        if(I('get.action'==2)){
            $user = M('User')->select();
            $this->action = 2;
        }else{
            $user = M('Admin_user')->select();
            $this->action = 1;
        }
        $this->assign('user',$user);
        $this->display();
    }

}