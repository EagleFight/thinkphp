<?php
namespace Admin\Model;
use Think\Model;
class UserModel extends Model {
    function se(){
        dump($this->select());
    }
}