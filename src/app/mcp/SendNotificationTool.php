<?php

declare(strict_types=1);

namespace app\mcp;

use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\database\model\AppModel;
use app\database\model\NotificationModel;
use app\utils\WechatException;
use app\utils\WorkWechatApp;
use nova\plugin\mcp\McpTool;

class SendNotificationTool extends McpTool
{
    private const array PRIORITIES = ['info', 'success', 'warning', 'error'];

    public function __construct()
    {
        $channels = array_map(
            fn (AppModel $app) => "{$app->short_name}（{$app->name}）",
            AppDao::getInstance()->list()
        );

        parent::__construct('send_notification', '向用户推送一条通知（通知中心 + 已配置的企业微信）。返回通知详情链接。', [
            'type' => 'object',
            'properties' => [
                'channel' => [
                    'type' => 'string',
                    'description' => '渠道短标识。可用渠道：' . (implode('、', $channels) ?: '暂无，请让用户先在后台创建渠道'),
                ],
                'title' => [
                    'type' => 'string',
                    'description' => '一句话说清结果，30 字以内，带上项目或任务名',
                ],
                'message' => [
                    'type' => 'string',
                    'description' => 'Markdown 正文：关键细节与用户下一步要做什么，不要贴大段日志',
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => self::PRIORITIES,
                    'description' => 'success=完成，error=失败，warning=需要用户处理，info=其他（默认）',
                ],
                'actions' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string'],
                    'description' => '操作链接，按钮名 => URL，例如 {"查看日志": "https://..."}',
                ],
            ],
            'required' => ['channel', 'title'],
        ]);
    }

    public function execute(array $arguments): array
    {
        $app = AppDao::getInstance()->shortName($arguments['channel']);
        if ($app === null) {
            return $this->result("渠道不存在：{$arguments['channel']}", true);
        }

        $priority = $arguments['priority'] ?? 'info';
        if (!in_array($priority, self::PRIORITIES, true)) {
            return $this->result('priority 只能是 ' . implode('/', self::PRIORITIES), true);
        }

        $model = NotificationDao::getInstance()->post(new NotificationModel([
            'app' => $app->id,
            'title' => $arguments['title'],
            'message' => $arguments['message'] ?? '',
            'priority' => $priority,
            'actions' => $arguments['actions'] ?? [],
            't' => time(),
        ]));

        try {
            WorkWechatApp::push($app, $model);
        } catch (WechatException $e) {
            return $this->result("通知已保存（{$model->viewUrl()}），但企业微信推送失败：{$e->getMessage()}", true);
        }

        return $this->result("已通知用户：{$model->viewUrl()}");
    }

    private function result(string $text, bool $isError = false): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]], 'isError' => $isError];
    }
}
