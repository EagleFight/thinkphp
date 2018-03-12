<?php
namespace Admin\Controller;
use Think\Controller;
class TestController extends Controller {
    public function index(){
        $user = D('TestUser');
        $res = $user->relation(true)->select();
        dump($res);
    }

    public function test(){
        $user = D('TestCard');
        $res = $user->relation(true)->select();
        dump($res);
    }
}