<?php

declare(strict_types=1);

namespace app\controller\index;

use app\Application;
use app\mcp\SendNotificationTool;
use nova\framework\http\Response;
use nova\plugin\mcp\McpController;
use nova\plugin\mcp\McpResponse;
use nova\plugin\mcp\McpServer;

use function nova\framework\config;

/**
 * POST /mcp：鉴权与发布接口共用全局 authorization（请求头可带 Bearer 前缀，或 ?authorization=）。
 */
class Mcp extends McpController
{
    private const string INSTRUCTIONS = <<<'EOF'
本服务把消息推送到用户的手机和通知中心，用户不在屏幕前也能收到。

以下情况调用 send_notification：
- 耗时任务（构建、测试、部署、迁移、批量处理等）结束，无论成功还是失败
- 需要用户做决定、补充信息或授权才能继续
- 发现需要用户尽快关注的问题

不要通知：每个小步骤、普通对话回复、用户正在实时看着的结果。同一件事只发一次。

写法：
- title 一句话说清结果，带上项目或任务名，例如「notify 依赖升级完成」
- message 用 Markdown 写关键数据和用户下一步要做什么，不贴大段日志
- priority：成功 success，失败 error，需要用户处理 warning，其他 info
- 有 PR、构建日志、页面地址等链接时放进 actions
EOF;

    protected function createMcpServer(): McpServer
    {
        return (new McpServer(Application::SYSTEM_NAME, '1.0.0', '向用户推送通知'))
            ->setInstructions(self::INSTRUCTIONS);
    }

    protected function registerComponents(): void
    {
        // 鉴权通过后再注册：工具构造时会读库生成渠道列表
    }

    public function handleMcpRequest(): Response
    {
        $headers = array_change_key_case($this->request->getHeaders());
        $auth = (string)($headers['authorization'] ?? $this->request->arg('authorization') ?? '');
        $auth = preg_replace('/^Bearer\s+/i', '', trim($auth));
        if (strlen($auth) <= 6 || !hash_equals((string)config('authorization'), $auth)) {
            return McpResponse::error($this->mcpRequest->getId(), -32001, 'Unauthorized');
        }

        $this->mcpServer->registerTool(new SendNotificationTool());
        return parent::handleMcpRequest();
    }
}
