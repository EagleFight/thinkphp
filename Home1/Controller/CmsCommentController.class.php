<?php
namespace Home\Controller;

use Common\Util\Tree;
use Common\Model\CmsCommentModel;
use Common\Model\CmsCommentLikeModel;

class CmsCommentController extends HomeController
{
    public function show($article_id)
    {
        $where = array();
        $where['article_id'] = intval($article_id);
        
        $CmsCommentModel = new CmsCommentModel();
        $comment_list = $CmsCommentModel->where($where)->order('like_num desc, comment_id asc')->select();
        
        $Tree = new Tree();
        // 所有评论
        $comment_tree = $Tree->list_to_tree($comment_list, 'comment_id', 'parent_comment_id');
        // 关于我的
        $comment_mine = $Tree->list_search($comment_tree, array('user_id' => $this->user_id));
        
        // 评论点赞关系
        $where = array();
        $where['article_id'] = $article_id;
        $where['user_id']    = $this->user_id;
        
        $CmsCommentLikeModel = new CmsCommentLikeModel();
        $comment_user_like_list = $CmsCommentLikeModel->where($where)->getField('comment_id', true);
        
        $this->assign('meta_title', '评论');
        $this->assign('comment_tree', $comment_tree);
        $this->assign('comment_mine', $comment_mine);
        $this->assign('comment_user_like_list', $comment_user_like_list);
        $this->display();
    }
    
    public function post()
    {
        $article_id = I('get.aid', 0, 'intval');
        $user_id    = $this->user_id;
        $content    = I('content', '');
        $parent_comment_id = I('cid', 0, 'intval');
        
        $CmsCommentModel = new CmsCommentModel();
        $result = $CmsCommentModel->post($article_id, $user_id, $content, $parent_comment_id);
        if($result){
            $this->success('评论成功!');
        }else{
            $this->error($CmsCommentModel->getError());
        }        
    }
    
    public function likeComment()
    {
        $comment_id = I('cid', 0, 'intval');
        $user_id    = $this->user_id;
        
        $CmsCommnetLikeModel = new CmsCommentLikeModel();
        $result = $CmsCommnetLikeModel->like($comment_id, $user_id);
        if($result){
            $this->success('点赞评论成功');
        }else{
            $this->error($CmsCommnetLikeModel->getError());
        }
    }
    
    public function unlikeComment()
    {
        $comment_id = I('cid', 0, 'intval');
        $user_id    = $this->user_id;
        
        $CmsCommnetLikeModel = new CmsCommentLikeModel();
        $result = $CmsCommnetLikeModel->unlike($comment_id, $user_id);
        if($result){
            $this->success('取消点赞评论成功');
        }else{
            $this->error($CmsCommnetLikeModel->getError());
        }
    }
}