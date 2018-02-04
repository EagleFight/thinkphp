<?php
namespace Admin\Controller;

class IndexController extends BaseController {
    public function index(){
        $this->display();
    }

    public function hello(){
        $this->ajaxReturn('wuwu');
    }

    public function haha(){
        $this->ajaxReturn('50000000000');
    }
}