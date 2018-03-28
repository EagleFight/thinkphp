<?php
/**
 * Created by PhpStorm.
 * User: xdw
 * Date: 2017/6/1
 * Time: 11:34
 */


namespace Home\Controller;

use Think\Controller;

class IndexController extends HomeController
{
   

    public function index()
    {

        //左侧导航列表
        $where['is_nav']='1';
        $nav_list = M("Cms_category")->where($where)->order('type asc')->select();

//        置顶三条
        $tick=M("CmsArticle")->where("type='1' and is_pass='1' ")->order('article_id desc')->LIMIT(0,3)->select();
        $this->assign('stick',$tick);

        //培训学习
        $sty_article=M("CmsStudy")->where("is_rec=1")->order('post_time desc')->LIMIT(0,5)->select();
        $this->assign('sty_article',$sty_article);



//        图片推荐
//        $wherePic['category_id']=$nav_list[0]['category_id'];
        $wherePic['is_pass']="1";
        $wherePic['is_rec']=1;

//        $pic=M("CmsArticle")->where($wherePic)->order('post_time desc')->LIMIT(0,1)->find();
//        $this->assign('pic',$pic);
//        $pic2cat=$nav_list[0]['category_id'];
        $pic2=M("CmsArticle")->where($wherePic)->order('post_time desc')->LIMIT(0,5)->select();
        if (count($pic2)%2==0){
             $num=count($pic2)-1;
        }else{
             $num=count($pic2);
        }
        $this->assign('pic2',$pic2);
        $this->assign('num',$num);



//        banner

        $timeWhere=date("Y-m-d H:i:s",time());

        // $banWhere="start_time <= '$timeWhere' AND end_time >= '$timeWhere'";

        $banner=M("CmsBanner")->order("sort asc")->select();
        $this->assign('banner',$banner);
        //根据分组获取配置
        $map['status'] = array('egt', '0'); //禁用和正常状态
        $map['name'] = array('in', 'WEB_WX_MA,WEB_APP');
        $banner_list = D('SystemConfig')->where($map)->order('sort asc,id asc')->select();
        $this->assign('banner_list',$banner_list);
        //热门推荐
        $hot_article=M("CmsArticle")->where("is_pass='1' ")->order('view_num desc')->LIMIT(0,6)->select();
        $this->assign('hot_article',$hot_article);





        //最新活动
        $activity_list = M("Activity")->order('sort asc')->LIMIT(0,4)->select();
        $this->assign('activity_list', $activity_list);
        $this->assign('nav_list', $nav_list);

        $this->display();
    }
    public function ajax_article_index(){
        //        置顶三条
        $tick=M("CmsArticle")->where("type='1' and is_pass='1' ")->order('article_id desc')->LIMIT(0,3)->select();
        $this->assign('stick',$tick);

        $where['is_nav']='1';
        $nav_list = M("Cms_category")->where($where)->order('type asc')->select();
//        图片推荐
        $wherePic['category_id']=$nav_list[0]['category_id'];
        $wherePic['is_pass']="1";
        $wherePic['is_rec']=1;
        $pic=M("CmsArticle")->where($wherePic)->order('post_time desc')->LIMIT(0,1)->find();
        $this->assign('pic',$pic);
        $pic2cat=$nav_list[0]['category_id'];
        $pic2=M("CmsArticle")->where("is_rec=1 and is_pass='1' and  category_id !=$pic2cat ")->order('post_time desc')->LIMIT(0,4)->select();
        if (count($pic2)%2==0){
            $num=count($pic2)+1;
        }else{
            $num=count($pic2);
        }
        $this->assign('pic2',$pic2);
        $this->assign('num',$num);
        $content = $this->fetch('index2');
        $this->ajaxReturn(['status'=>200,'data'=>$content]);

    }
    public function ajax_article(){
        // 中间文章列表
        $page = I('page', 1, 'intval');
        $id = I('id', 1, 'intval');
        if($id !=0){$whereArt['category_id']=$id;}
        $whereArt['is_pass']="1";
        $row=10;
        $pic=rand(3,8);


        $article_list= M("CmsArticle")->where($whereArt)->page($page, $row)->order('article_id desc')->select();
        $is_rec=array();
        $a=0;
        foreach ($article_list as $key => $val){
            if($val['is_rec']==1){

                $is_rec[$a]=$val['article_id'];
                $a++;
            }
        }
        $hot=$is_rec[rand(0,$a)];

        //将为空的数组去除
        if(!empty(array_filter($article_list))){
            $this->assign('article_list',$article_list);
            $this->assign('pic',$pic);
         
            $this->assign('hot',$hot);
            $content = $this->fetch('artlist');

            $this->ajaxReturn(['status'=>200,'data'=>$content,'hot'=>$hot]);
        }else{
             $this->ajaxReturn(['status'=>400,'data'=>"没有了"]);
        }

    }


}