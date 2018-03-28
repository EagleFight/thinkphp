<?php
namespace Home\Controller;

use Common\Model\CmsCategoryModel;
use Common\Model\CmsArticleModel;

class CmsCategoryController extends HomeController
{
    public function index($id)
    {
        $where = array();
        if(is_numeric($id)){
            $where['category_id'] = $id;
        }else{
            $where['permalink'] = $id;
        }
        
        $CmsCategoryModel = new CmsCategoryModel();
        $category_info = $CmsCategoryModel->where($where)->find();
        
        $this->assign('meta_title', $category_info['title']);
        $this->assign('category_info', $category_info);
        $this->display();
    }
    
    public function getList()
    {
        $category_id = I('id', 0, 'intval');
        $page = I('p', 1, 'intval');
        $row  = I('row', 20, 'intval');
        
        $where = array();
        $where['category_id'] = $category_id;
        
        $CmsArticleModel = new CmsArticleModel();
        $article_list = $CmsArticleModel->where($where)->page($page, $row)->order('article_id desc')->select();
        
        $has_more = count($article_list) < $row ? false : true;
        
        $this->assign('article_list', $article_list);
        $this->assign('has_more', $has_more);
        
        $content = $this->fetch('_Widget/CmsCategory/getList');
        $this->show($content);
    }
}