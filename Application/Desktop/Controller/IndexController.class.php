<?php
namespace Desktop\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        if(IS_AJAX){
            $tpl = $this->fetch('tpl_icon_list');
            $this->ajaxReturn($tpl);
        }
        $this->display();
    }
}