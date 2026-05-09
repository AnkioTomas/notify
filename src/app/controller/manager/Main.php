<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\Application;
use nova\framework\http\Response;
use nova\plugin\login\manager\PwdLoginManager;
use nova\plugin\login\manager\SSOLoginManager;
use nova\plugin\tpl\ViewResponse;

class Main extends BaseController
{
    protected ViewResponse $viewResponse;

    public function init(): ?Response
    {
        $data = parent::init();
        if (!empty($data)) {
            return $data;
        }
        $this->viewResponse = new ViewResponse();

        $this->viewResponse->init(
            '',
            [
                'title' => Application::SYSTEM_NAME,
                'header' => $this->userModel->avatar,
            ]
        );

        if (!$this->request->isPjax()) {
            $menu =  [
                [
                    "title" => "通知列表",
                    "icon" => "campaign",
                    "url" => "/notifications",
                    "pjax" => true
                ],
                [
                    "title" => "通知渠道",
                    "icon" => "notifications",
                    "url" => "/channel",
                    "pjax" => true
                ],
                [
                    "title" => "发布通知",
                    "icon" => "rss_feed",
                    "url" => "/token",
                    "pjax" => true
                ],
                [
                    "title" => "企业微信",
                    "icon" => "forum",
                    "url" => "/wechat",
                    "pjax" => true
                ],
                [
                    "title" => "账户安全",
                    "url" => "/account",
                    "icon" => "security",
                    "pjax" => true
                ],
                [
                    "title" => "统一认证登录",
                    "url" => "/sso",
                    "icon" => "vpn_key",
                    "pjax" => true
                ],
            ];

            return $this->viewResponse->asTpl("layout", [
                'menuConfig' => $menu
            ]);
        }

        return null;
    }

    public function index(): Response
    {
        return Response::asRedirect("/channel");
    }

    public function notifications(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function channel(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function account(): Response
    {
        return $this->viewResponse->asTpl(PwdLoginManager::TPL_PASSWORD, [
            "username" => $this->userModel->username,
        ]);
    }

    public function sso(): Response
    {
        return $this->viewResponse->asTpl(SSOLoginManager::TPL_SSO);
    }

    public function token(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function wechat(): Response
    {
        return $this->viewResponse->asTpl();
    }

}
