<?php

declare(strict_types=1);

namespace app\utils;

use app\Application;
use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\framework\event\EventManager;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\login\db\Dao\UserDao;
use nova\plugin\tpl\ViewResponse;
use PDO;
use PDOException;
use Throwable;

use function nova\framework\config;
use function nova\framework\uuid;

/**
 * 首次安装向导。
 *
 * - 未安装时只允许访问 /install 及静态资源
 * - 安装完成后访问 /install 自动跳转到 /
 */
class Installer
{
    public static function register(): void
    {
        EventManager::addListener('route.before', static function ($event, &$uri) {
            self::handle((string)$uri);
        }, 0);
    }

    private static function handle(string $uri): void
    {
        if (self::isStaticAsset($uri)) {
            return;
        }

        $installed = (bool)(config('installed') ?? false);
        $isInstallRoute = str_starts_with($uri, '/install');

        if ($installed && !$isInstallRoute) {
            return;
        }

        if ($installed && $isInstallRoute) {
            throw new AppExitException(Response::asRedirect('/'), 'Already installed');
        }

        if (!$isInstallRoute) {
            throw new AppExitException(Response::asRedirect('/install'), 'Not installed');
        }

        $response = match ($uri) {
            '/install'        => self::showPage(),
            '/install/submit' => self::handleSubmit(),
            default           => Response::asRedirect('/install'),
        };

        throw new AppExitException($response, 'Exit by Installer');
    }

    private static function isStaticAsset(string $uri): bool
    {
        return str_starts_with($uri, '/static')
            || str_starts_with($uri, '/favicon');
    }

    private static function showPage(): Response
    {
        $view = new ViewResponse();
        $view->init('', [
            'title' => Application::SYSTEM_NAME,
        ]);
        return $view->asTpl('install');
    }

    private static function handleSubmit(): Response
    {
        $request = Context::instance()->request();
        if (!$request->isPost()) {
            return self::json(405, '请使用 POST 提交安装信息');
        }

        $db = [
            'host' => trim((string)$request->post('db_host', '127.0.0.1')),
            'port' => (int)$request->post('db_port', 3306),
            'username' => trim((string)$request->post('db_username', '')),
            'password' => (string)$request->post('db_password', ''),
            'db' => trim((string)$request->post('db_name', '')),
            'charset' => 'utf8mb4',
            'type' => 'mysql',
        ];

        $systemName = trim((string)$request->post('system_name', ''));
        $authorization = trim((string)$request->post('authorization', ''));
        $wechat = [
            'corpid' => trim((string)$request->post('corpid', '')),
            'to_user' => trim((string)$request->post('to_user', '')),
            'token' => trim((string)$request->post('token', '')),
            'aes_key' => trim((string)$request->post('aes_key', '')),
        ];

        if ($db['host'] === '' || $db['username'] === '' || $db['db'] === '') {
            return self::json(400, '数据库主机 / 账号 / 库名不能为空');
        }

        $dbError = self::testDatabase($db);
        if ($dbError !== null) {
            return self::json(400, '数据库连接失败：' . $dbError);
        }

        if ($systemName === '') {
            $systemName = Application::SYSTEM_NAME;
        }
        if ($authorization === '') {
            $authorization = md5(uuid());
        }

        try {
            config('db.host', $db['host']);
            config('db.port', $db['port']);
            config('db.username', $db['username']);
            config('db.password', $db['password']);
            config('db.db', $db['db']);
            config('db.charset', $db['charset']);
            config('db.type', $db['type']);

            config('login.systemName', $systemName);
            config('authorization', $authorization);

            config('work_wechat.corpid', $wechat['corpid']);
            config('work_wechat.to_user', $wechat['to_user']);
            config('work_wechat.token', $wechat['token']);
            config('work_wechat.aes_key', $wechat['aes_key']);

            UserDao::getInstance()->initTable();
            AppDao::getInstance()->initTable();
            NotificationDao::getInstance()->initTable();

            config('installed', true);
        } catch (Throwable $e) {
            Logger::error('安装失败：' . $e->getMessage(), $e->getTrace());
            return self::json(500, '安装失败：' . $e->getMessage());
        }

        return self::json(200, '安装完成', [
            'data' => [
                'redirect' => '/login',
                'authorization' => $authorization,
                'adminPassword' => self::readAdminPassword(),
            ],
        ]);
    }

    private static function testDatabase(array $db): ?string
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['db'],
            $db['charset']
        );

        try {
            new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return null;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    private static function readAdminPassword(): ?string
    {
        $file = ROOT_PATH . DS . 'runtime' . DS . 'admin_password.txt';
        if (!is_file($file)) {
            return null;
        }
        $content = trim((string)file_get_contents($file));
        if ($content === '') {
            return null;
        }
        if (preg_match('/密码[:：]\s*(\w+)/u', $content, $m)) {
            return $m[1];
        }
        return null;
    }

    private static function json(int $code, string $msg, array $extra = []): Response
    {
        return Response::asJson(array_merge(['code' => $code, 'msg' => $msg], $extra));
    }
}
