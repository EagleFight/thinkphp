<?php
namespace Home\Controller;

use Common\Model\UsersModel;
use Common\Controller\WechatController;

class TestwxController extends WechatController
{
    public function wxlogin(){
        $redirect_uri = urlencode( "http://chinanursecare.cn/Home/Testwx/getWxInfo/" );
        $url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx42ae67c3bff15eeb&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
        header("Location:".$url);       
    }    
    public function getWxInfo(){
        $code = $_GET["code"];
             
        //取得网页授权的 access_token 和 openid
        $oauth2Url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx42ae67c3bff15eeb&secret=d7aaaddcb5b38ac4e96e2ff14655c95e&code=$code&grant_type=authorization_code";
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