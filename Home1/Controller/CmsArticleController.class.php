<?php
namespace Home\Controller;

use Common\Model\CmsArticleModel;
use Common\Model\CmsCommentModel;
use Common\Util\Tree;
use Common\Model\CmsUserSubscribeModel;
use Common\Model\CmsArticleLikeModel;
use Common\Model\CmsArticleCollectModel;
use Common\Model\CmsCommentLikeModel;
use Common\Model\UserWalletModel;

class CmsArticleController extends HomeController
{
    public function info($id)
    {
        // 文章详情
        $CmsArticleModel = new CmsArticleModel();
        $article_info = $CmsArticleModel->view($id);
        
        // 是否关注
        $to_user_id   = $article_info['user_id'];
        $from_user_id = $this->user_id;
        
        $CmsUserSubscribeModel = new CmsUserSubscribeModel();
        $is_subscribe = $CmsUserSubscribeModel->isSubscribe($to_user_id, $from_user_id);
        
        $article_id = $article_info['article_id'];
        $user_id    = $this->user_id;
        // 是否点赞
        $CmsArticleLikeModel = new CmsArticleLikeModel();
        $is_like = $CmsArticleLikeModel->isLike($article_id, $user_id);
        
        // 是否收藏
        $CmsArticleCollectModel = new CmsArticleCollectModel();
        $is_collect = $CmsArticleCollectModel->isCollect($article_id, $user_id);
        
        // 推荐 文章
        $where = array();
        $where['category_id'] = $article_info['category_id'];
        $where['article_id'] = array('gt', $article_info['article_id']);
        
        $article_list = $CmsArticleModel->where($where)->limit(6)->select();
        
        // 文章评论
        $where = array();
        $where['article_id'] = $article_info['article_id'];
        
        $CmsCommentModel = new CmsCommentModel();
        $comment_list = $CmsCommentModel->where($where)->order('like_num desc, comment_id asc')->select();
        
        $Tree = new Tree();
        $comment_tree = $Tree->list_to_tree($comment_list, 'comment_id', 'parent_comment_id');
        
        // 评论点赞关系
        $where = array();
        $where['article_id'] = $article_info['article_id'];
        $where['user_id']    = $this->user_id;
        
        $CmsCommentLikeModel = new CmsCommentLikeModel();
        $comment_user_like_list = $CmsCommentLikeModel->where($where)->getField('comment_id', true);
        
        $this->assign('meta_title', $article_info['title']);
        $this->assign('article_info', $article_info);
        $this->assign('is_subscribe', $is_subscribe);
        $this->assign('is_like', $is_like);
        $this->assign('is_collect', $is_collect);
        $this->assign('article_list', $article_list);
        $this->assign('comment_tree', array_slice($comment_tree, 0, 3));
        $this->assign('comment_user_like_list', $comment_user_like_list);
        $this->assign('js', $this->getJs());
        $this->display();
    }
    
    public function subscribeUser()
    {
        $to_user_id   = I('get.uid', 0, 'intval');
        $from_user_id = $this->user_id;
        
        $CmsUserSubscribeModel = new CmsUserSubscribeModel();
        $result = $CmsUserSubscribeModel->subscribe($to_user_id, $from_user_id);
        if($result){
            $this->success('订阅成功');
        }else{
            $this->error($CmsUserSubscribeModel->getError());
        }
    }
    
    public function unsubscribeUser()
    {
        $to_user_id   = I('get.uid', 0, 'intval');
        $from_user_id = $this->user_id;
        
        $CmsUserSubscribeModel = new CmsUserSubscribeModel();
        $result = $CmsUserSubscribeModel->unsubscribe($to_user_id, $from_user_id);
        if($result){
            $this->success('取消订阅成功');
        }else{
            $this->error($CmsUserSubscribeModel->getError());
        }
    }
    
    public function likeArticle()
    {
        $article_id = I('get.aid', 0, 'intval');
        $user_id    = $this->user_id;
        
        $CmsArticleLikeModel = new CmsArticleLikeModel();
        $result = $CmsArticleLikeModel->like($article_id, $user_id);
        if($result){
            $this->success('文章点赞成功');
        }else{
            $this->error($CmsArticleLikeModel->getError());
        }
    }
    
    public function unlikeArticle()
    {
        $article_id = I('get.aid', 0, 'intval');
        $user_id    = $this->user_id;
        
        $CmsArticleLikeModel = new CmsArticleLikeModel();
        $result = $CmsArticleLikeModel->unlike($article_id, $user_id);
        if($result){
            $this->success('取消文章点赞成功');
        }else{
            $this->error($CmsArticleLikeModel->getError());
        }
    }
    
    public function collectArticle()
    {
        $article_id = I('get.aid', 0, 'intval');
        $user_id    = $this->user_id;
        
        $CmsArticleCollectModel = new CmsArticleCollectModel();
        $result = $CmsArticleCollectModel->collect($article_id, $user_id);
        if($result){
            $this->success('文章收藏成功');
        }else{
            $this->error($CmsArticleCollectModel->getError());
        }
    }
    
    public function uncollectArticle()
    {
        $article_id = I('get.aid', 0, 'intval');
        $user_id    = $this->user_id;
    
        $CmsArticleCollectModel = new CmsArticleCollectModel();
        $result = $CmsArticleCollectModel->uncollect($article_id, $user_id);
        if($result){
            $this->success('取消文章收藏成功');
        }else{
            $this->error($CmsArticleCollectModel->getError());
        }
    }
    
    public function share()
    {
        $article_id = I('get.aid', 0, 'intval');
        $user_id = $this->user_id;

        $UserWalletModel = new UserWalletModel();
        $result = $UserWalletModel->shareArticle($article_id, $user_id);
        if($result){
            $bi = C('HBI_SHARE');
            $this->success("分享成功,+{$bi}H币");
        }else{
            $this->error($UserWalletModel->getError());
        }
    }
}