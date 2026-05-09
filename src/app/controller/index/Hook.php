<?php

declare(strict_types=1);

namespace app\controller\index;

use app\utils\WechatCrypt;

use function nova\framework\config;

use nova\framework\core\Logger;
use nova\framework\http\Response;
use nova\framework\route\Controller;

class Hook extends Controller
{
    /**
     * 企业微信回调入口。
     * GET：URL 校验，返回 echostr 解密明文。
     * POST：解密消息体后落 log，返回空 200（不做被动回复，按需走主动消息接口）。
     */
    public function index(): Response
    {
        $token = config("work_wechat.token");

        $aes_key = config("work_wechat.aes_key");

        $corpid = config("work_wechat.corpid");

        $crypt = new WechatCrypt($token, $aes_key, $corpid);

        $sig = $this->request->get('msg_signature', '');
        $ts = $this->request->get('timestamp', '');
        $nonce = $this->request->get('nonce', '');

        if ($this->request->isGet()) {
            $echo = $this->request->get('echostr', '');
            return Response::asText($crypt->verifyUrl($sig, $ts, $nonce, $echo));
        }

        $xml = $crypt->decryptMsg($sig, $ts, $nonce, $this->request->raw());
        // TODO
        Logger::info("wechat hook recv: {$xml}");
        return Response::asText('');
    }

}
