<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        dump(M('User')->select());
       $this->display(); 
    }
}