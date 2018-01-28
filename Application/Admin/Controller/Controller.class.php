<?php
namespace Admin\Controller;

class Controller extends \Think\Controller
{
    protected function _initialize(){

        //===============模拟登录==============
        session('admin_login',M('admin_user')->find(1));

        #公共控制器直接跳出认证、权限相关检测
        $pub_controller=['Pub'];
        if(in_array(CONTROLLER_NAME,$pub_controller))return;

        #session登录身份验证
        if(!session('?admin_login'))redirect(U('Pub/login'));

        #检测超管权限
//        if(session('admin_login.super_auth')==1)return;

        #auth权限验证
        $auth=D('Auth');
        if(!$auth->init(CONTROLLER_NAME,session('admin_login.id')))$this->error('没有权限访问');

        #生成菜单，路径，auth->init()不仅验证了权限，也生成nav,path
        $this->mainnav=$auth->mainnav;
        $this->subnav=$auth->subnav;
        $this->path=$auth->path;
    }
}