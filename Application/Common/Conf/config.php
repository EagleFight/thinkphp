<?php
return array(
	//'配置项'=>'配置值'
    'DB_TYPE'               =>  'mysql',                        // 数据库类型
    'DB_HOST'               =>  '127.0.0.5',                    // 服务器地址
    'DB_NAME'               =>  'tangyanwu',                         // 数据库名
    'DB_USER'               =>  'root',                         // 用户名
    'DB_PWD'                =>  '',  // 密码
    'DB_PORT'               =>  '3306',                         // 端口
    'DB_PREFIX'             =>  '',          // 数据库表前缀


    //Module
    'MODULE_ALLOW_LIST' => array('Home', 'Admin'),
    'DEFAULT_MODULE'    => 'Home',
//    'URL_MODULE_MAP'    => array('mng'=>'admin'),


);