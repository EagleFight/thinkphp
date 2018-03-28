<?php
namespace Home\Controller;
use Common\Model\UsersModel;
use Common\Util\Str;

class PrizeController extends HomeController{
	public function index(){
		$user = $this -> getUser();
		$userId = $user['user_id'];
		$UsersModel = new UsersModel();
		$M = M('User_wallet');
		$Prize = M('Prize_log');
		$hbArr = $M->where("user_id='$userId'")->find();
		$hb = number_format($hbArr['money']);
		$prize_num = $hbArr['prize_num'];
		$CmsPage = M('Cms_page');
		$activity = $CmsPage -> where("name = '抽奖活动说明'") ->find();		
		// 获取最近获奖记录
		// $whereAll = array('prize_id' => array('NEQ', 5));
		$whereMy = array('user_id'=>$userId);
		$newly = $Prize -> order('gettime desc')->limit(30)->select();
		foreach ($newly as $key => $val) {
			$uid = $val['user_id'];
			$newlyUser = $UsersModel->find($uid);
			$newly[$key]['nickname'] = $newlyUser['nickname'];
		}
		// 个人获奖记录
		$mylog = $Prize->where($whereMy) -> order('gettime desc')->select();

		$isAuth = $user['is_auth'];
		$mobile = $user['mobile'];
		if(empty($mobile)){
			$mobile = -1;
		}
		$this->assign('user_info', $this->user_info);
		$this->assign('activity', $activity);
		$this->assign('isAuth', $isAuth);
		$this->assign('mobile', $mobile);
		$this->assign('allHb', $hb);
		$this->assign('prize_num', $prize_num);
		$this->assign('newly',$newly);
		$this->assign('mylog', $mylog);
		$this->assign('empty','<p class="empty">暂时没有中奖纪录</p>');
		$this->assign('js', $this->getJs());
		$this -> display();
	}

	public function saveRecord($record){
		$user = $this -> getUser();
		$user_id = $user['user_id'];
		$record['user_id'] = $user_id;
		$record['gettime'] = date("Y-m-d H:i:s", time());
		$M = M('Prize_log');
		$res = $M -> add($record);
		// 减抽奖次数
		$Num = M('User_wallet');
		$Num->where("user_id='$user_id'")->setDec('prize_num');
	}
	public function convert(){
		$user = $this -> getUser();
		$userId = $user['user_id'];
		$M = M('User_wallet');
		$hbArr = $M->where("user_id='$userId'")->find();		
		if(IS_POST){
			$post = I('post.');
			$status = $post['status'];
			// 1表示次数，2表示hb换红包
			$nums = $post['nums'];
			$decHb = $this->numtohb($nums);
			$money = $this -> hbtormb($nums);
			$log_w['user_id'] = $log['user_id'] = $userId;				
			$log['change_type'] = $status;
			$log['change_num'] = $nums;
			$log_w['time'] = $log['change_time'] = date("Y-m-d H:i:s", time());
			$Change = M('Change_log');
			$Wal = M('user_log_wallet');			
			if($status==1){
				$res = $Change -> add($log);
				// 更新
				$Num = M('User_wallet');
				$Num->where("user_id='$userId'")->setInc('prize_num',$nums);				
				$Num->where("user_id='$userId'")->setDec('money',$decHb);
				$msg = '恭喜兑换'.$nums.'次抽奖';
				$log_w['money'] = -$decHb;
				$log_w['remark'] = '兑换'.$nums.'次抽奖-'.$decHb.'H币';
				$Wal -> add($log_w);
				$this->ajaxReturn(['status'=>1,'msg'=>$msg]);				
			}elseif ($status==2) {
				$res = $Change -> add($log);
				// 更新
				$Num = M('User_wallet');
				$Num->where("user_id='$userId'")->setInc('money_rmb',$money);				
				$Num->where("user_id='$userId'")->setDec('money',$nums);
				$msg = '恭喜兑换'.$money.'元红包';
				$log_w['money'] = -$nums;
				$log_w['remark'] = '兑换'.$money.'元红包-'.$nums.'H币';
				$Wal -> add($log_w);				
				$this->ajaxReturn(['status'=>2,'msg'=>$msg]);
			}else{
				$this->ajaxReturn(['status'=>0,'msg'=>'非法请求']);
			}		
		}
		$hb = number_format($hbArr['money']);

		$prize_num = $hbArr['prize_num'];
		$deg_num = $this -> hbtonum($hbArr['money']);
		$deg_red = $this -> hbtormb($hbArr['money']);

		$this->assign('allHb', $hb);
		$this->assign('useHb', $hbArr['money']);
		$this->assign('prize_num', $prize_num);						
		$this->assign('deg_num', $deg_num);						
		$this->assign('deg_red', $deg_red);						
		$this -> display();
	}
	public function qrcode(){
		$M = M('hubo_kefu');
		$kefu = $M->order('rand()')->find();
		$this->assign('kefu',$kefu);
		$this -> display();
	}
	public function saveAddr(){
		if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法请求']);
		$user = $this -> getUser();
		$user_id = $user['user_id'];
		$data = I('post.');
		$data['user_id'] = $user['user_id'];
		$M = M('user_addr');
		$res = $M -> where("user_id='$user_id'")->find();
		if($res){
			$this->ajaxReturn(['status'=>3,'msg'=>'已提交收货地址，奖品即将发放！']);
		}else{
			$result = $M -> add($data);
			if($result){
				$this->ajaxReturn(['status'=>1,'msg'=>'已提交收货地址，奖品即将发放！']);
			}else{
				$this->ajaxReturn(['status'=>2,'msg'=>'失败！']);
			}			
		}

	}	
	public function getUser(){
		$U = new UsersModel();
		$user = $U->find($this->user_id);
		return $user;	
	}
	public function prize(){
		if(!IS_POST)$this->ajaxReturn(['status'=>0,'msg'=>'非法请求']);
		$user = $this -> getUser();
		$user_id = $user['user_id'];
		$isAuth = $user['is_auth'];
		$getUid = I('post.uid');
		if($getUid != 1 || $isAuth != 2){
        	echo -2;
        	exit();
		}
		$Num = M('User_wallet');
		$Num_ = $Num->where("user_id='$user_id'")->find();
		if($Num_['prize_num'] == 0){
        	echo -1;
        	exit();			
		}
		$M = M('Prizes');
		$prize_arr  = $M -> order('prize_id asc') -> select();
		foreach ($prize_arr as $k=>$v) {
		    $arr[$v['prize_id']] = $v['v'];

		}

		$prize_id = $this -> getRand($arr); //根据概率获取奖项id 

		foreach($prize_arr as $k=>$v){ //获取前端奖项位置
		    if($v['prize_id'] == $prize_id){
		     $prize_site = $k;
		     break;
		    }
		}
		$res = $prize_arr[$prize_id - 1]; //中奖项 

		$record = array();
		// 参与奖随机
		$partArr = array('1.8','2.8','3.8','4.8');
		if($prize_id == 4){
		  $parK =  array_rand($partArr,1);
		  $res['con'] = "获得<span>".$partArr[$parK]."</span>元红包";
		  $record['prized'] = "获得".$partArr[$parK]."元红包";
		}else{
		  $record['prized'] = "获得".$res['prize_detail'];
		}

		$data['prize_name'] = $res['prize'];
		$data['prize_site'] = $prize_site;
		$data['prize_id'] = $prize_id;
		$data['prize_path'] = $res['path'];
		$data['prize_cover'] = $res['cover'];
		$data['prize_con'] = $res['con'];
		$data['prize_exp'] = $res['exp'];
		$data['prize_gobtn'] = $res['gobtn'];
		$data['prize_link'] = $res['link'];

		$remark = Str::randString(6, 1);
		$record['prize_id'] = $prize_id;
		$record['remark'] = $remark;
		$this -> saveRecord($record);
		// 发送验证码
		$keyword2 = date("Y-m-d H:i", time());
		$sendData = array(
		    "first"    => "恭喜你抽中了".$res['prize_detail']."，请添加客服微信号发送验证码领取奖品！",
		    "keyword1" => $record['prized'],
		    "keyword2" => $keyword2,
		    "remark"   => "领取奖品的验证码是： ".$remark,
		);
		$url = "http://chinanursecare.cn/Home/Prize/qrcode";		
		$this -> sendmsg($user_id,$remark,$sendData,$url);
		echo json_encode($data);
	}
	public function sendmsg($userId,$remark,$sendData,$url){
		$notice = $this -> getNotice();
		$wxUser = M('User_oauth');
		$resWx = $wxUser -> where("user_id='$userId'")->find();
		$templateId = 'gEERQRxFxVRLjqIQrhghYbtEesG5RtZ0ZPe9tsBrqQs';
		$openid = $resWx['openid'];
		sleep(3);
		$messageId = $notice->to($openid)->uses($templateId)->andUrl($url)->data($sendData)->send();
	}
	public function getRand($proArr){
		$data = '';
	    $proSum = array_sum($proArr); 

	    foreach ($proArr as $k => $v) { 
	        $randNum = mt_rand(1, $proSum);
	        if ($randNum <= $v) {
	            $data = $k;
	            break;
	        } else {
	            $proSum -= $v;
	        }
	    }
	    unset($proArr);

	    return $data;
	}		
	public function hbtonum($hb){
		return intval(floor($hb/500));
	}
	public function numtohb($nums){
		return intval($nums*500);
	}	
	public function hbtormb($hb){
		return ($hb/100);	
	}				
}