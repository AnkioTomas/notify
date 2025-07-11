<?php

declare(strict_types=1);

namespace app\controller\notify;

use nova\framework\http\Response;
use nova\plugin\tpl\ViewResponse;

class Main extends BaseController
{
    protected ViewResponse $viewResponse;
    /**
     * 处理 URI，如果以 data:image 或 http(s) 开头则保持原样，否则前面加上 /upload/
     *
     * @param  string $uri
     * @return string
     */
    public function processUri(string $uri): string
    {
        if (preg_match('/^(data:image\/|https?:\/\/)/i', $uri)) {
            return $uri;
        }
        return '/upload/' . ltrim($uri, '/');
    }
    public function init(): ?Response
    {
        $ret = parent::init();
        if (!empty($ret)) {
            return $ret;
        }
        $this->viewResponse = new ViewResponse();

        $this->viewResponse->init(
            '',
            [
                'title' => 'AnkioのNotify',
                'header' => $this->processUri($this->userModel->avatar),
                'nickname' => $this->userModel->display_name,
            ]
        );
        // 非多用户系统，仅管理员登录
        if ($this->userModel->id !== 1) {
            return $this->redirectTo("/403");
        }

        if (!$this->request->isPjax()) {
            //第一个用户是管理员
            $menu =  [
                [
                    "title" => "应用列表",
                    "icon" => "apps",
                    "url" => "/app",
                    "pjax" => true
                ],
                [
                    "title" => "个人中心",
                    "icon" => "person",
                    "url" => "/center",
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
        if ($this->isAdmin()) {
            return Response::asRedirect("/dashboard");
        } else {
            return Response::asRedirect("/user/center");
        }
    }

    public function dashboard(): Response
    {
        if (!$this->isAdmin()) {
            return Response::asRedirect("/403");
        }
        return $this->viewResponse->asTpl("dashboard", [
            "apps" => $this->group3(AppDao::getInstance()->getCount()),
            "user" => $this->group3(UserDao::getInstance()->getCount()),
        ]);
    }

    private function group3($str): string
    {
        return rtrim(chunk_split($str, 3, ','), ',');
    }

    public function app(): Response
    {
        if (!$this->isAdmin()) {
            return Response::asRedirect("/403");
        }
        return $this->viewResponse->asTpl();
    }
    public function user(): Response
    {
        if (!$this->isAdmin()) {
            return Response::asRedirect("/403");
        }
        return $this->viewResponse->asTpl();
    }
    public function center(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function logout(): Response
    {
        Session::getInstance()->destroy();
        return $this->redirectTo("/login");
    }
}
