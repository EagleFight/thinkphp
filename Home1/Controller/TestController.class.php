<?php
namespace Home\Controller;

use Think\Controller;
use EasyWeChat\Foundation\Application;
use Common\Util\Tree;
use Common\Model\CmsCommentModel;
use Common\Model\CmsCommentLikeModel;

class TestController extends Controller
{
    // public function wxlogin(){
    //     $callback = 'http://ceshi.chinanursecare.cn/Home/Public/getWxInfo/';
    //     session_start();
    //     $state  = md5(uniqid(rand(), TRUE));
    //     $_SESSION['wx_state'] = $state; //存到SESSION
    //     $callback = urlencode($callback);
    //     $wxurl = "https://open.weixin.qq.com/connect/qrconnect?appid=".WX_APPID."&redirect_uri={$callback}&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
    //     header("Location: $wxurl");        
    // }    
    public function getWxInfo(){
        $code = $_GET["code"];
             
        //取得网页授权的 access_token 和 openid
        $oauth2Url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".WX_APPID."&secret=".WX_SECRET."&code=$code&grant_type=authorization_code";
        $oauth2 = $this -> getJson($oauth2Url);
        $access_token = $oauth2["access_token"]; 
        $openid = $oauth2['openid'];

        //根据全局access_token和openid查询用户信息 
        $get_user_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $userinfo = $this -> getJson($get_user_info_url);
         
        //打印用户信息
        print_r($userinfo);

    }
    public function getJson($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }  
}