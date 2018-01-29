<?php
namespace Admin\Controller;
use Think\Controller;
class PubController extends Controller {
    public function login(){
        if(session('?admin_login'))$this->error('您已经登录过了，即将进入首页',U('Index/index'));
        if(IS_POST){
            $username = I('post.login');
            $M=M('Admin_user');
            $user=$M->where(['login'=>$username])->find();
            session('admin_login',$user);
            $this->success('登录成功',U('Index/index'));
        }
        $this->display();
    }
}