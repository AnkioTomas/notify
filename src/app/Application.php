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

use nova\framework\App;

use function nova\framework\route;

use nova\framework\route\Route;

class Application extends App
{
    public function onFrameworkStart(): void
    {
        Route::getInstance()
            // API 路由

            // UI 路由
            ->get("/", route("manager", "main", "index"))
            ->get("/notify/{channel}", route("notify", "main", "channel"))

            ->get("/account", route("manager", "main", "account"))
            ->get("/sso", route("manager", "main", "sso"))
            ->get("/channel", route("manager", "main", "channel"))

            ->get("/token", route("manager", "main", "token"))
            ->get("/token/get", route("manager", "token", "get"))
            ->get("/token/reset", route("manager", "token", "reset"))

            ->get("/channel/list", route("manager", "channel", "list"))
            ->post("/channel/edit", route("manager", "channel", "edit"))
            ->post("/channel/del", route("manager", "channel", "del"))

            ->get("/wechat", route("manager", "main", "wechat"))
            ->getOrPost("/wechat/config", route("manager", "wechat", "config"))

            ->getOrPost("/hook", route("index", "hook", "index"))

            ->post("/{channel}", route("index", "main", "publish"))
            ->get("/{short}", route("index", "main", "short"))
        ;
    }

    public const string SYSTEM_NAME =  "AnkioのNotify";

}
