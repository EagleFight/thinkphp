<?php
namespace Home\Controller;

class AnswerController extends HomeController
{
	protected function _initialize(){
		parent::_initialize();
		//获取问答系统未读消息
		$Ans=D('Answer');
		$AnsRel=D('AnswerReply');
		$user_newest_read=M('answer_newest_read')->where(['user_id'=>$this->user_id])->getfield('max_read_id')?:0;
		$this->user_newest_read=$user_newest_read;
		$max_ans_id=$Ans->max('id');
		if($user_newest_read<$max_ans_id){
			$unread['today']=$Ans->where(['id'=>['gt',$user_newest_read]])->count('id');
		}else{
			$unread['today']=0;
		}
		if($ask_ids=$Ans->where(['user_id'=>$this->user_id])->getfield('id',true)){
			$unread['ask']=$AnsRel->where(['answer_id'=>['in',$ask_ids],'read'=>0])->count('id');
		}else{
			$unread['ask']=0;
		}
		if($answer_ids=$AnsRel->where(['user_id'=>$this->user_id,'comment_to'=>0])->getfield('id',true)){
			$unread['answer']=$AnsRel->where(['comment_to'=>['in',$answer_ids],'read'=>0])->count('id');
		}else{
			$unread['answer']=0;
		}
		$this->unread=$unread;
		$recomm_ask=$Ans->where(['state'=>0])->relation(['user','reply'])->order('id desc')->limit(5)->select(['thumb'=>[100,70,3]]);
		$this->recomm_ask=$recomm_ask;
		$this->assign('user_info',$this->user_info);
	}
    public function index(){
		$this->indTitle='问答';
        $this->display();
    }
	public function category($category_id=0){
		$M=M('answer_category');
		$data=$M->order('order_id desc,id')->select();
		if(!$category_id)$category_id=current($data)['id'];
		$this->category_id=$category_id;
		$this->category=$data;
		$this->indTitle='问题分类';
		$this->display('index');
	}
	public function today(){
		$max_ans_id=D('Answer')->max('id');
		if($this->user_newest_read!=$max_ans_id){
			$M=M('answer_newest_read');
			if($this->user_newest_read===0){
				$M->add(['user_id'=>$this->user_id,'max_read_id'=>$max_ans_id]);
			}else{
				$M->where(['user_id'=>$this->user_id])->setfield(['max_read_id'=>$max_ans_id]);
			}
		}
		$unread=$this->unread;
		$unread['today']=0;
		$this->unread=$unread;
		$this->indTitle='今日问题';
		$this->display('index');
	}
	public function myQuestion(){
		$this->indTitle='我的提问';
		$this->display('index');
	}
	public function myAnswer(){
		$this->indTitle='我的回答';
		$this->display();
	}
	public function getIndexList($category_id=0,$type=0,$return=false){
		$pagesize=6;
		$page=I('get.p')?:1;
		$Answer=D('Answer');
		$category_id&&$where['category_id']=$category_id;
		switch($type){
			case '1'://today
				$relation=['user','category','reply'];
				$order='id desc';
				break;
			case '2'://my question
				$relation=['user','category','reply','unread'];
				$where['user_id']=$this->user_id;
				$order='id desc';
				break;
			default:
				$relation=['user','category','reply'];
				$order='state,date_format(last_reply_time,"%Y-%m-%d"),hits desc,id desc';
		}
		$data=$Answer->relation($relation)->where($where)->page($page,$pagesize)->order($order)->select(['thumb'=>[140,140,3]]);
		if($return){//为个人中心提供调用，直接返回data
			$page=new \Common\Util\Page($Answer->where($where)->count(),$pagesize);
			return[
				'data'=>$data,
				'page'=>$page->show()
			];
		}
		$this->ajaxReturn([
			'data'=>$data,
			'end'=>count($data)<$pagesize?true:false
		]);
	}
	public function getMyAnswerList($return=false){
		$pagesize=5;
		$page=I('get.p')?:1;
		$Reply=D('AnswerReply');
		$data=$Reply->relation(true)->where(['user_id'=>$this->user_id,'comment_to'=>0])->page($page,$pagesize)->order('id desc')->select(['thumb'=>[140,140,3]]);
		if($data){
			$category=M('answer_category')->getfield('id,name');
			foreach($data as $k=>$v){
				$data[$k]['category_name']=$category[$v['category_id']];
				$data[$k]['content']=editor_display($data[$k]['content']);
			}
		}
		if($return){//为个人中心提供调用，直接返回data
			$page=new \Common\Util\Page($Reply->where(['user_id'=>$this->user_id,'comment_to'=>0])->count(),$pagesize);
			return[
				'data'=>$data,
				'page'=>$page->show()
			];
		}
		$this->ajaxReturn([
			'data'=>$data,
			'end'=>count($data)<$pagesize?true:false
		]);
	}
	public function dtl($id){
		$Answer=D('Answer');
		if(!$data=$Answer->relation(['user','category','reply'])->find($id))$this->error('没有此数据！');
		if(IS_POST){
			if(mb_strlen(I('post.editorValue'))<6)$this->error('多写几个字吧，比如写10个字，万一就提交成功了呢');
			$SystemMedia=D('SystemMedia');
			$medias=$SystemMedia->batchUpload('images');
			$reply=[
				'answer_id'=>$id,
				'comment_to'=>0,
				'user_id'=>$this->user_id,
				'content'=>I('post.editorValue'),
				'media_ids'=>$medias?implode(',',array_column($medias,'id')):''
			];
			if(D('AnswerReply')->add($reply)){
				//更新问题最后回复时间
				$Answer->setfield(['id'=>$id,'last_reply_time'=>date('Y-m-d H:i:s')]);
				$this->success('提交回答成功！',U('dtl?id='.$id));
			}else{
				$this->error('提交回答失败，请稍后重试！');
			}
		}else{
			$Answer->hit($id);
			if($data['user_id']==$this->user_id){
				//设置当前用户的提问的关联所有回答为已读
				D('AnswerReply')->where(['answer_id'=>$id])->setfield(['read'=>1]);
				//自己提问模版
				//$tpl='dtl_self';
			}
			$this->data=$data;
			$this->indTitle='问答详情';
			$this->display($tpl);
		}
	}
	public function getDtlReplyList($id){
		$pagesize=5;
		$page=I('get.p')?:1;
		$answer=M('Answer')->find($id);
		if($answer['state']==1){
			//已解决（采纳）的问题，采纳答案排序置顶，未采纳的按有用数倒序
			$order='adopted desc,useful desc,id desc';
		}else{
			//未解决问题，有用数大于10按有用数倒序，小于10按回答时间倒序
			$order='(case when useful>=10 then 1 else 0 end) desc,id desc';
		}
		$D=D('AnswerReply');
		$data=$D->relation(['user','comment_count'])->where(['answer_id'=>$id])->page($page,$pagesize)->order($order)->select();
		//获取是否已经存在“有用”关系
		$U=M('answer_reply_useful');
		foreach($data as $k=>$v){
			$data[$k]['is_useful']=$U->where(['user_id'=>$this->user_id,'reply_id'=>$v['id']])->find()?1:0;
			$data[$k]['top2_comments']=$D->where(['answer_id'=>0,'comment_to'=>$v['id']])->relation('user')->limit(2)->order('id desc')->select();
			$data[$k]['content']=editor_display($data[$k]['content']);
		}
		$this->ajaxReturn([
			'data'=>$data,
			'end'=>count($data)<$pagesize?true:false
		]);
	}
	public function getReplyCommentList($id,$from=false){
		$D=D('AnswerReply');
		$pagesize=$from?10:2;
		$page=I('get.p')?:1;
		$data=$D->where(['answer_id'=>0,'comment_to'=>$id])->relation('user')->page($page,$pagesize)->order('id desc')->select();
		foreach($data as $k=>$v){
			$data[$k]['content']=editor_display($data[$k]['content']);
		}
		$this->ajaxReturn([
			'data'=>$data,
			'end'=>count($data)<$pagesize?true:false
		]);
	}
	public function commentsReply($id){
		$D=D('AnswerReply');
		if(!$D->find($id))$this->ajaxReturn(['state'=>0,'msg'=>'没有此回答']);
		if(IS_POST){
			if(mb_strlen(I('post.content'))<6)$this->ajaxReturn(['state'=>0,'msg'=>'多写几个字吧，比如写10个字，万一就提交成功了呢']);
			$reply=[
				'answer_id'=>0,
				'comment_to'=>$id,
				'user_id'=>$this->user_id,
				'content'=>I('post.content')
			];
			if($addid=$D->add($reply)){
				$state=1;
				$data=$D->relation('user')->field('user_id,content,create_time')->find($addid);
			}else{
				$state=0;
				$msg='回复失败，请稍后重试';
			}
		}else{
			$state=0;
			$msg='请使用POST方式提交';
		}
		$this->ajaxReturn([
			'state'=>$state,
			'data'=>$data,
			'msg'=>$msg
		]);
	}
	public function useful($id){
		//提问者为[采纳答案]，其他人则为[有用]
		$M=M('answer_reply_useful');
		if($M->where(['user_id'=>$this->user_id,'reply_id'=>$id])->find()){
			$return=0;
		}else{
			$return=$M->add(['user_id'=>$this->user_id,'reply_id'=>$id])?1:0;
		}
		$return&&M('answer_reply')->where(['id'=>$id])->setinc('useful',1);
		$this->ajaxreturn($return);
	}
	public function adopt($id,$replyid){
		$reply_ids=explode(',',$replyid);
		if(count($reply_ids)<1)$this->error('请采纳至少一个回答');
		$Answer=D('Answer');
		$Answer->startTrans();
		if($Answer->adopt($id,$reply_ids,$this->user_id)){
			$Answer->commit();
			$this->success('采纳操作成功');
		}else{
			$Answer->rollback();
			$this->error('采纳操作失败：'.$Answer->geterror());
		}
	}
	public function reply($id){
		$D=D('Answer');
		if(!$data=$D->relation(['user','category'])->find($id))$this->error('没有此问题');
		if($data['user_id']==$this->user_id)$this->error('不能自问自答哦！');
		if(IS_POST){
			if(mb_strlen(I('post.content'))<6)$this->error('多写几个字吧，比如写10个字，万一就提交成功了呢');
			$SystemMediaModel=new SystemMediaModel();
			$media_ids_arr=[];
			foreach(explode(',',I('post.pics')) as $v){
				$filename=$this->downloadTemporary($v);
				$result=$SystemMediaModel->saveWechat($filename);
				if(!$result['error'])$media_ids_arr[]=$result['id'];
			}
			$reply=[
				'answer_id'=>$id,
				'comment_to'=>0,
				'user_id'=>$this->user_id,
				'content'=>I('post.content'),
				'media_ids'=>implode(',',$media_ids_arr)
			];
			if(D('AnswerReply')->add($reply)){
				//更新问题最后回复时间
				$D->setfield(['id'=>$id,'last_reply_time'=>date('Y-m-d H:i:s')]);
				redirect(U('dtl?id='.$id));
			}else{
				$this->error('提交回答失败，请稍后重试！');
			}
		}else{
			$this->wxconfig=$this->getJs()->config(['previewImage','chooseImage','uploadImage'],false);
			$this->data=$data;
			$this->meta_title='我来回答';
			$this->display();
		}
	}
	public function comments($id){
		$D=D('AnswerReply');
		$U=M('answer_reply_useful');
		if(!$data=$D->relation(['user','comment_count'])->find($id))$this->error('无此回答');
		
		//设置当前用户的回答的关联所有评论为已读
		if($data['user_id']==$this->user_id)$D->where(['comment_to'=>$id])->setfield(['read'=>1]);
		$data['content']=editor_display($data['content']);
		$this->is_useful=$U->where(['user_id'=>$this->user_id,'reply_id'=>$id])->find()?1:0;
		$this->data=$data;
		$this->indTitle='查看回复';
		$this->display();
	}
	public function getDtlCommentList($id){
		$D=D('AnswerReply');
		$pagesize=6;
		$page=I('get.p')?:1;
		$data=$D->where(['answer_id'=>0,'comment_to'=>$id])->relation('user')->page($page,$pagesize)->order('id desc')->select();
		$this->ajaxReturn([
			'data'=>$data,
			'end'=>count($data)<$pagesize?true:false
		]);
	}
	public function ask(){
		$M=M('answer_category');
		$category=$M->order('order_id desc,id')->getfield('id,name');
		$wallet_info=D('UserWallet')->find($this->user_id);
		if(IS_POST){
			$D=D('Answer');
			$post=I('post.');
			if(mb_strlen($post['title'])<5)$this->error('提问标题稍微长一点喔~');
			if(!array_key_exists($post['category_id'],$category))$this->error('问题类别选择错误');
			if(mb_strlen($post['content'])<6)$this->error('您的提问内容太精简了，再多写点什么吧~');
			if($post['reward']<0 or $post['reward']>$wallet_info['money'])$this->error('您的H币余额不足');
			$SystemMediaModel=new SystemMediaModel();
			$media_ids_arr=[];
			foreach(explode(',',$post['pics']) as $v){
				$filename=$this->downloadTemporary($v);
				$result=$SystemMediaModel->saveWechat($filename);
				if(!$result['error'])$media_ids_arr[]=$result['id'];
			}
			$add=[
				'user_id'=>$this->user_id,
				'category_id'=>$post['category_id'],
				'title'=>$post['title'],
				'content'=>$post['content'],
				'reward'=>$post['reward'],
				'media_ids'=>implode(',',$media_ids_arr)
			];
			if($id=$D->add($add)){
				//扣H币
				D('UserWallet')->fundChg($this->user_id,-$post['reward'],'提问悬赏-'.$post['reward'].'HB');
				redirect(U('dtl?id='.$id));
			}else{
				$this->error('提交提问失败！');
			}
		}else{
			$this->wxconfig=$this->getJs()->config(['previewImage','chooseImage','uploadImage'],false);
			$this->category=$category;
			$this->wallet=$wallet_info;
			$this->meta_title='发布提问';
			$this->display();
		}
	}
	public function userPub($id){
		if(!$user=M('users')->find($id))$this->error('查无此人！');
		$this->user=$user;
		$this->is_self=$this->user_id==$id?true:false;
		$this->followed=M('answer_follow')->where(['user_id'=>$this->user_id,'follow_id'=>$id])->find()?true:false;
		$this->meta_title='发布的文章';
		$this->display();
	}
	public function userAns($id){
		if(!$user=M('users')->find($id))$this->error('查无此人！');
		$this->user=$user;
		$this->is_self=$this->user_id==$id?true:false;
		$this->followed=M('answer_follow')->where(['user_id'=>$this->user_id,'follow_id'=>$id])->find()?true:false;
		$this->meta_title='已回答的问题';
		$this->display();
	}
	public function getUserAnsList($id){
		$pagesize=6;
		$page=I('get.p')?:1;
		$data=D('AnswerReply')->relation(['answer','user'])->page($page,$pagesize)->where(['user_id'=>$id,'comment_to'=>0])->order('id desc')->select();
		$this->ajaxReturn([
			'data'=>$data,
			'end'=>count($data)<$pagesize?true:false
		]);
	}
	public function follow($id){
		if(!M('users')->find($id))$this->error('关注的对象不存在');
		if($this->user_id==$id)$this->error('不可以自己关注自己哦');
		$M=M('answer_follow');
		$condition=['user_id'=>$this->user_id,'follow_id'=>$id];
		if($M->where($condition)->find()){
			$M->where($condition)->delete()?redirect(getenv("HTTP_REFERER")):$this->error('取消关注失败，请稍后重试');
		}else{
			$M->add($condition)?redirect(getenv("HTTP_REFERER")):$this->error('关注失败，请稍后重试');
		}
	}
}