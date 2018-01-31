<?php
namespace Desktop\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        if(IS_AJAX){
            $this->ajaxReturn(['status'=>'http://127.0.0.2/index.php/Admin/Index/index.html']);
        }
        $this->display();
    }
}