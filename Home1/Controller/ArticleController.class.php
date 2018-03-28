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

class ArticleController extends UserController
{

//    发布文章
    public function publish(){
        if(IS_AJAX){
            $model=M('CmsArticle');
            $post=I('post.');

            $Media=D('SystemMedia');
                if($_FILES['file']['error']!=0) $this->ajaxReturn(['status'=>400,'data'=>"图片上传失败"]);
                $file=$Media->singleUpload($_FILES['file']);
                if($file){
                    $post['cover_media_ids']=$file['id'];
                }else{
                    $this->error($Media->geterror());
                }

            $post['user_id']=$this->user_id;
            $post['post_time']=date("Y-m-d H:i:s",time());
            $post['content']=$post['editorValue'];
            $model->create($post);
            $result = $model->add();
            if ($result){
                $this->ajaxReturn(['status'=>200]);
            }else{

                $this->ajaxReturn(['status'=>400,'data'=>$model->getError()]);
            }

        }else{


            $where['is_nav']='1';
            $nav_list = M("Cms_category")->where($where)->order('category_id asc')->select();
            $arExp = M('Cms_page') -> where("name = '投稿须知'") ->find();
            $this->assign('arexp', $arExp);              
            $this->assign('nav_list', $nav_list);
            $this->display();
        }
    }

    public function myarticle($p = 1, $row = 10){

        if(IS_AJAX){
            $where['user_id']=$this->user_id;
            $is_pass=I("post.is_pass");
            if($is_pass !="200"){
                $where['is_pass']=$is_pass;
            }
            $list = M("CmsArticle")->where($where)->page($p, $row)->order('article_id desc')->select();
            $page = new \Common\Util\Page(M('CmsArticle')->where($where)->count(), $row);

            $this->assign('page', $page->show());
            $this->assign('list', $list);

            $content = $this->fetch('list');
            $this->ajaxReturn(['status'=>200,'data'=>$content,]);
        }else{


        $where['user_id']=$this->user_id;
        $list = M("CmsArticle")->where($where)->page($p, $row)->order('article_id desc')->select();
        $page = new \Common\Util\Page(M('CmsArticle')->where($where)->count(), $row);

        $allList=M("CmsArticle")->field('view_num,like_num,comment_num,collect_num')->where($where)->select();
        $count=array(
            'view_num'=>0,
            'like_num'=>0,
            'comment_num'=>0,
            'collect_num'=>0
        );
        foreach ($allList as $key=>$val ){
            $count['view_num']+=$val['view_num'];
            $count['like_num']+=$val['like_num'];
            $count['comment_num']+=$val['comment_num'];
            $count['collect_num']+=$val['collect_num'];
        }
        $this->assign('count', $count);
        $this->assign('page', $page->show());
        $this->assign('list', $list);
        $this->display();
    }
    }
    /**
     * 上传文件
     * @param $dir = 上传类型(image、flash、media、file)
     */
    public function upload(){
        exit(D('SystemMedia')->upload());
    }



}