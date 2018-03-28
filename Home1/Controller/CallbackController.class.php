<?php
namespace Home\Controller;

use Common\Controller\WechatController;

class CallbackController extends WechatController
{
    public function oauth()
    {
        $this->getOauth();
    }
}