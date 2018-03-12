/*
Navicat MySQL Data Transfer

Source Server         : tangyanwu
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : wuwu

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2018-03-13 00:05:27
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `admin_user`
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户主键id',
  `login` varchar(30) NOT NULL COMMENT '用户登录账号',
  `password` varchar(30) NOT NULL COMMENT '用户登录密码',
  `logintime` varchar(20) DEFAULT NULL COMMENT '用户登录时间',
  `create_time` varchar(20) NOT NULL COMMENT '账号注册时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='后台管理系统用户表';

-- ----------------------------
-- Records of admin_user
-- ----------------------------
INSERT INTO `admin_user` VALUES ('1', '唐彦武', 'tangyanwu', null, '2018-01-26');

-- ----------------------------
-- Table structure for `auth_group`
-- ----------------------------
DROP TABLE IF EXISTS `auth_group`;
CREATE TABLE `auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `rules` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='总后台auth授权组表';

-- ----------------------------
-- Records of auth_group
-- ----------------------------
INSERT INTO `auth_group` VALUES ('1', '管理员', '1', '1,2,3,4,5,6,7,8');
INSERT INTO `auth_group` VALUES ('2', '系统维护员', '1', '1,2,3,4,5,6，7');
INSERT INTO `auth_group` VALUES ('3', '', '1', '');

-- ----------------------------
-- Table structure for `auth_group_access`
-- ----------------------------
DROP TABLE IF EXISTS `auth_group_access`;
CREATE TABLE `auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='总后台auth授权权限表';

-- ----------------------------
-- Records of auth_group_access
-- ----------------------------
INSERT INTO `auth_group_access` VALUES ('1', '1');
INSERT INTO `auth_group_access` VALUES ('12', '1');
INSERT INTO `auth_group_access` VALUES ('13', '1');
INSERT INTO `auth_group_access` VALUES ('14', '1');
INSERT INTO `auth_group_access` VALUES ('15', '1');
INSERT INTO `auth_group_access` VALUES ('16', '1');
INSERT INTO `auth_group_access` VALUES ('17', '1');
INSERT INTO `auth_group_access` VALUES ('18', '1');
INSERT INTO `auth_group_access` VALUES ('19', '1');
INSERT INTO `auth_group_access` VALUES ('20', '1');

-- ----------------------------
-- Table structure for `auth_rule`
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL DEFAULT '',
  `url` char(80) NOT NULL,
  `title` char(20) NOT NULL DEFAULT '',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT '',
  `pid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `orderid` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COMMENT='总后台auth授权规则表';

-- ----------------------------
-- Records of auth_rule
-- ----------------------------
INSERT INTO `auth_rule` VALUES ('1', 'Index', 'Index/index', '首页', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('2', 'User', 'User/index', '用户管理', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('3', 'Article', 'Article/index', '文章管理', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('4', 'Video', 'Video/index', '视频管理', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('5', 'Audio', 'Audio/index', '音乐管理', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('6', 'MatchData', 'bao/index', '保密账号', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('7', 'System', 'System/index', '系统管理', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('8', 'Accounts', 'Accounts/index', '权限管理', '1', '1', '', '0', '0');
INSERT INTO `auth_rule` VALUES ('9', 'Index/index', 'Index/index', '欢迎登录', '2', '1', '', '1', '0');
INSERT INTO `auth_rule` VALUES ('10', 'User/index', 'User/index', '用户列表', '2', '1', '', '2', '0');
INSERT INTO `auth_rule` VALUES ('11', 'User/add', 'User/add', '用户添加', '2', '1', '', '2', '0');
INSERT INTO `auth_rule` VALUES ('12', 'User/detele', 'User/detele', '用户删除', '2', '1', '', '2', '0');
INSERT INTO `auth_rule` VALUES ('13', 'Article', 'Article/index', '文章列表', '2', '1', '', '3', '0');
INSERT INTO `auth_rule` VALUES ('14', 'Push/index', 'Push/index', '新闻列表', '2', '1', '', '5', '0');
INSERT INTO `auth_rule` VALUES ('15', 'Push/msglist', 'Push/msglist', '消息推送', '2', '1', '', '5', '0');
INSERT INTO `auth_rule` VALUES ('16', 'Push/advertisement', 'Push/advertisement', '广告管理', '2', '1', '', '5', '0');
INSERT INTO `auth_rule` VALUES ('17', 'Users/index', 'Users/index', '用户列表', '2', '1', '', '4', '0');
INSERT INTO `auth_rule` VALUES ('18', 'Users/sponsor', 'Users/sponsor', '赞助商管理', '2', '1', '', '4', '0');
INSERT INTO `auth_rule` VALUES ('19', 'Users/teamwork', 'Users/teamwork', '合作商管理', '2', '1', '', '4', '0');
INSERT INTO `auth_rule` VALUES ('20', 'Users/place', 'Users/place', '线下场地管理', '2', '1', '', '4', '0');
INSERT INTO `auth_rule` VALUES ('21', 'Users/referee', 'Users/referee', '裁判管理', '2', '1', '', '4', '0');
INSERT INTO `auth_rule` VALUES ('22', 'MatchData/index', 'MatchData/index', '赛事录入', '2', '1', '', '6', '0');
INSERT INTO `auth_rule` VALUES ('23', 'System/version', 'System/version', '版本管理', '2', '1', '', '7', '0');
INSERT INTO `auth_rule` VALUES ('24', 'System/index', 'System/index', '表单管理', '2', '1', '', '7', '0');
INSERT INTO `auth_rule` VALUES ('25', 'System/account', 'System/account', '账号分配', '2', '1', '', '7', '0');
INSERT INTO `auth_rule` VALUES ('26', 'System/area', 'System/area', '区域管理', '2', '1', '', '7', '0');

-- ----------------------------
-- Table structure for `test_card`
-- ----------------------------
DROP TABLE IF EXISTS `test_card`;
CREATE TABLE `test_card` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `code` int(6) NOT NULL,
  `uid` mediumint(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of test_card
-- ----------------------------
INSERT INTO `test_card` VALUES ('1', '155696', '3');
INSERT INTO `test_card` VALUES ('2', '151711', '1');
INSERT INTO `test_card` VALUES ('3', '244555', '4');

-- ----------------------------
-- Table structure for `test_comment`
-- ----------------------------
DROP TABLE IF EXISTS `test_comment`;
CREATE TABLE `test_comment` (
  `id` smallint(8) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `uid` smallint(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of test_comment
-- ----------------------------
INSERT INTO `test_comment` VALUES ('1', '我的弟1条留言', '4');
INSERT INTO `test_comment` VALUES ('2', '我的弟2条留言', '4');
INSERT INTO `test_comment` VALUES ('3', '我的弟3条留言', '4');
INSERT INTO `test_comment` VALUES ('4', '我的弟1条留言', '1');
INSERT INTO `test_comment` VALUES ('5', '我的弟2条留言', '1');

-- ----------------------------
-- Table structure for `test_user`
-- ----------------------------
DROP TABLE IF EXISTS `test_user`;
CREATE TABLE `test_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(255) NOT NULL COMMENT '用户姓名',
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of test_user
-- ----------------------------
INSERT INTO `test_user` VALUES ('1', '唐彦武', '1240035958@qq.com');
INSERT INTO `test_user` VALUES ('2', '汤圆', 'sdgfdgdg163@.com');
INSERT INTO `test_user` VALUES ('3', '蜡笔小新', '150458767@qq.com');
INSERT INTO `test_user` VALUES ('4', '小丸子', '');

-- ----------------------------
-- Table structure for `user`
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(20) NOT NULL AUTO_INCREMENT COMMENT '用户主键id',
  `name` varchar(50) NOT NULL,
  `create_time` varchar(20) NOT NULL COMMENT '用户注册账号时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('1', 'wuwu', '2018-01-01');
