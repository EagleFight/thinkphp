<?php
namespace Home\Controller;

use Common\Model\ActivityModel;
use Common\Model\ActivityCollectModel;

class ActivityController extends HomeController
{
    public function index()
    {
        $this->assign('meta_title', '活动专区');
        $this->display();
    }
    
    public function info($id)
    {
        $ActivityModel = new ActivityModel();
        $activity_info = $ActivityModel->find($id);
        
        $templateFile = $activity_info['detail_template'] ? '_Activity/'.$activity_info['detail_template'] : '_Activity/default';
        
        $ActivityCollectModel = new ActivityCollectModel();
        $is_collect = $ActivityCollectModel->isCollect($id, $this->user_id);
        
        $this->assign('meta_title', $activity_info['title']);
        $this->assign('activity_info', $activity_info);
        $this->assign('user_info', $this->user_info);
        $this->assign('is_collect', $is_collect);
        $this->assign('js', $this->getJs());
        $this->display($templateFile);
    }
    
    function getActList()
    {
        $page = I('p', 1, 'intval');
        $row  = I('row', 20 , 'intval');
        
        $ActivityModel = new ActivityModel();
        $activity_list = $ActivityModel->page($page, $row)->order('sort asc')->select();
        
        $has_more = count($activity_list) < $row ? false : true;
        
        $this->assign('activity_list', $activity_list);
        $this->assign('has_more', $has_more);
        
        $content = $this->fetch('_Widget/Activity/getList');
        $this->show($content);
    }
    
    public function collect()
    {
        $activity_id = I('id', 0, 'intval');
        $user_id = $this->user_id;
        
        $ActivityCollectModel = new ActivityCollectModel();
        $result = $ActivityCollectModel->collect($activity_id, $user_id);
        if($result){
            $this->success('关注成功');
        }else{
            $this->error('关注失败');
        }
    }
    
    public function uncollect()
    {
        $activity_id = I('id', 0, 'intval');
        $user_id = $this->user_id;
    
        $ActivityCollectModel = new ActivityCollectModel();
        $result = $ActivityCollectModel->uncollect($activity_id, $user_id);
        if($result){
            $this->success('取消关注成功');
        }else{
            $this->error('取消关注失败');
        }
    }
}