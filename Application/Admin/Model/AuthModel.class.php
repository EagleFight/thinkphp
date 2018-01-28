<?php
namespace Common\Model;
use Think\Auth;

class AuthModel extends Auth{
	private $cur_subnav;
	public $path,$mainnav,$subnav;
	public function init($name,$userid){
		if(!$this->check($name,$userid))return false;
		$this->buildPagePath();
		$this->getNav($name,$userid);
		return true;
	}
	private function getNav($controller_name,$userid){
		$M=M('auth_rule');
		$auth_list=$this->getAuthList($userid,1);
		$main_nav=$M->where(['name'=>['in',$auth_list]])->order('orderid')->select();

		$subnav=M('auth_rule')->where(['type'=>2])->order('orderid')->select();
		foreach($main_nav as $k=>$v){
		    foreach ($subnav as $key=>$val){
		       if($v['id']==$val['pid']){
                   $main_nav[$k]['sub'][] = $val;
               }
            }
//            if(strtolower($v['name'])==strtolower($controller_name)){
//                $cur_nav_main_id=$v['id'];
//                $main_nav[$k]['cur']=true;
//                break;
//            }
        }
//        foreach($main_nav as $k=>$v){
//            if(strtolower($v['name'])==strtolower($controller_name)){
//                $cur_nav_main_id=$v['id'];
//                $main_nav[$k]['cur']=true;
//                break;
//            }
//        }
//		foreach($subnav as $k=>$v){
//			if($this->cur_subnav==$v['id']){
//				$subnav[$k]['cur']=true;
//				break;
//			}
//		}
//        dump($main_nav);
		$this->mainnav=$main_nav;
		$this->subnav=$subnav;
	}
	private function buildPagePath(){
		$url=CONTROLLER_NAME.'/'.ACTION_NAME;
		$M=M('auth_rule');
		$rule_list=$M->where(['name'=>$url,'type'=>2])->getfield('id,url,title,pid');
		foreach($rule_list as $auth){
			$query=preg_replace('/^.+\?/U','',$auth['url']);
			if($query!=$auth['url']){
				parse_str($query,$param);
				if(I('get.'.key($param))==current($param)){
					$rule=$auth;
					break;
				}
			}else{
				$rule=$auth;
			}
		}
		$this->cur_subnav=$rule['id'];
		$main_rule=$M->find($rule['pid']);
		$path='<a href="'.U('Index/index').'">首页</a>';
		$path.=$main_rule['id']==1?'':' / <a href="'.U($main_rule['url']).'">'.$main_rule['title'].'</a>';
		$path.=' / <a href="'.U($rule['url']).'">'.$rule['title'].'</a>';
		$this->path=$path;
	}
}