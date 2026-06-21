<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace app;

use app\utils\Installer;
use nova\framework\App;

use function nova\framework\route;

use nova\framework\route\Route;

use nova\plugin\login\LoginTpl;

use nova\plugin\login\route\Permission;

class Application extends App
{
    public function onFrameworkStart(): void
    {
        Installer::register();
        LoginTpl::getInstance()->registerRouter('manager', 'main');
        Route::getInstance()
            // API 路由

            // UI 路由
            ->get("/", route("manager", "main", "index"))
            ->get("/notify/{channel}", route("notify", "main", "channel"))

            ->get("/token", route("manager", "main", "token"))
            ->get("/token/get", route("manager", "token", "get"))
            ->get("/token/reset", route("manager", "token", "reset"))

            ->get("/channel", route("manager", "main", "channel"))
            ->get("/channel/list", route("manager", "channel", "list"))
            ->post("/channel/edit", route("manager", "channel", "edit"))
            ->post("/channel/del", route("manager", "channel", "del"))

            ->get("/notifications/list", route("manager", "notification", "list"))
            ->get("/notifications", route("manager", "main", "notifications"))

            ->get("/wechat", route("manager", "main", "wechat"))
            ->getOrPost("/wechat/config", route("manager", "wechat", "config"))

            ->getOrPost("/hook", route("index", "hook", "index"))

            ->post("/{channel}", route("index", "main", "publish"))
            ->get("/{short}", route("index", "short", "detail"))
        ;

        Permission::getInstance()->registerPermissions('通知配置', "notify_manage", [
            'ANY /token*',
            'ANY /wechat',
            'ANY /wechat*',
            'ANY /channel*'
        ]);

        Permission::getInstance()->registerPermissions('只读通知', "notify_read", [
            'ANY /notify*',
            'ANY /notifications*',
        ]);

    }

    public const string SYSTEM_NAME =  "AnkioのNotify";

}
