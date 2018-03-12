<?php
namespace Admin\Model;
use Think\Model;
class TestUserModel extends Model\RelationModel {

    protected $_link = array(
        'TestCard'=>array(
            'mapping_type'=>self::HAS_ONE,
            'foreign_key'=>'uid',
            'class_name'=>'TestCard',
//            'mapping_name'=>'555'               //
            'mapping_fields'=>'code,uid',
            'as_fields'=>'code:mycode,uid',
//            'condition'=>'id>1'
        ),
        'TestComment'=>array(
            'mapping_type'=>self::HAS_MANY,
            'foreign_key'=>'uid',
            'mapping_fields'=>'content',
            'mapping_limit'=>'0,2'
        ),
    );
}