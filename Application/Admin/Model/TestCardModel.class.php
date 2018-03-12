<?php
namespace Admin\Model;
use Think\Model;
class TestCardModel extends Model\RelationModel {

    protected $_link = array(
        'TestUser'=>array(
            'mapping_type'=>self::BELONGS_TO,
            'foreign_key'=>'uid',
            'mapping_fields'=>'name,email',
            'as_fields'=>'name:user_name,email'
        ),
    );
}