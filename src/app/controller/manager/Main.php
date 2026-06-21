<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\database\model\AppModel;

use nova\framework\http\Response;
use nova\plugin\login\controller\BaseViewController;

class Main extends BaseViewController
{
    public function index(): Response
    {
        return Response::asRedirect($this->firstUri());
    }

    public function notifications(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function channel(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function token(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function wechat(): Response
    {
        return $this->viewResponse->asTpl();
    }

    private function subMenus(): array
    {
        $menu = [
            [
                "title" => "全部通知",
                "icon" => "",
                "url" => "/notifications",
                "pjax" => true,
                "match" => "^/notifications(?!\?.*app_id=)($|\?)",
            ],
        ];

        /**
         * @var $item AppModel
         */
        foreach (AppDao::getInstance()->list() as $item) {
            $menu[] = [
                "title" => $item->name,
                "icon" => "",
                "url" => "/notifications?app_id=".$item->id,
                "pjax" => true,
                "match" => "^/notifications\?([^#]*&)?app_id=" . $item->id . "(&|$)",
            ];
        }

        return $menu;
    }

    protected function getMenu(): array
    {
        return [
            [
                "title" => "通知列表",
                "icon" => "campaign",
                "pjax" => true,
                "sub" => $this->subMenus()
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

        ];
    }

}
