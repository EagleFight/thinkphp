<?php
namespace Admin\Controller;
use Think\Controller;
class BaseController extends Controller {
    protected function _initialize(){
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST, PUT');
    }
}