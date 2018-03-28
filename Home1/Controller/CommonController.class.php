<?php
namespace Home\Controller;

class CommonController extends HomeController
{
    public function wechatAddress()
    {
        $this->assign('js', $this->getJs());
        $this->display();
    }
}