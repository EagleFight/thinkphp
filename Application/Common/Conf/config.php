<?php
return array(
	//'配置项'=>'配置值'
    /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',                        // 数据库类型
    'DB_HOST'               =>  '127.0.0.2',                    // 服务器地址
    'DB_NAME'               =>  'wuwu',                         // 数据库名
    'DB_USER'               =>  'root',                         // 用户名
    'DB_PWD'                =>  '',  // 密码
    'DB_PORT'               =>  '3306',                         // 端口
    'DB_PREFIX'             =>  '',          // 数据库表前缀

    //Module
    'MODULE_ALLOW_LIST' => array('Home','Admin'),
    'DEFAULT_MODULE'    => 'Home',
//    'URL_MODULE_MAP'    => array('mng'=>'admin'),

    //模板相关配置
    'TMPL_PARSE_STRING' => array(
        '__PUBLIC__' => __ROOT__ . '/Public',
    ),

);