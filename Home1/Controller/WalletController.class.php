<?php
namespace Home\Controller;

use Common\Model\UserLogWalletModel;
use Common\Model\CmsPageModel;

class WalletController extends HomeController
{
    protected function _initialize()
    {
        parent::_initialize();
    
        if(!$this->user_info['mobile']){
            if(IS_AJAX){
                $this->error('请先绑定手机号' ,U('Public/register'));
            }else{
                $this->redirect('Public/register');
            }
        }
    }
    
    public function detail()
    {
        $this->assign('meta_title', '明细');
        $this->display();
    }
    
    public function getDetailList()
    {
        $page = I('p', 1, 'intval');
        $row  = I('row', 20, 'intval');
        
        $where = array();
        $where['user_id'] = $this->user_id;
        
        $UserLogWalletModel = new UserLogWalletModel();
        $log_list = $UserLogWalletModel->where($where)->page($page, $row)->order('log_id desc')->select();
        
        $has_more = count($log_list) < $row ? false : true;
        
        $this->assign('log_list', $log_list);
        $this->assign('has_more', $has_more);
        
        $content = $this->fetch('_Widget/Wallet/getDetailList');
        $this->show($content);
    }
    
    public function explain()
    {
        $where = array();
        $where['name'] = 'H币说明';
        
        $CmsPageModel = new CmsPageModel();
        $info = $CmsPageModel->where($where)->find();
        
        $this->assign('meta_title', 'H币说明');
        $this->assign('info', $info);
        $this->display();
    }
}