<?php

declare(strict_types=1);

namespace app\database\model;

use nova\framework\core\Context;
use nova\plugin\orm\object\Model;

class NotificationModel extends Model
{
    public int     $app         = 0;         // 频道名称
    public string  $title           = "";        // 消息标题
    public string  $message         = "";        // 消息内容（纯文本 / Markdown）
    public string  $priority            = "info"; // 消息类型： info, warning, error, success
    public array  $actions   = [];
    public int     $t               = 0;         // 发布时间戳（秒）
    public string $short_url = "";

    private const array PRIORITY_EMOJI = [
        'info'    => '🔵',
        'warning' => '🟡',
        'error'   => '🔴',
        'success' => '🟢',
    ];

    /** 企业文本里同一行两个 action 之间的分隔（emoji） */
    private const string WECHAT_ACTION_SEPARATOR = ' 🔹 ';

    public function getUnique(): array
    {
        return ['short_url'];
    }

    public function getNoEscape(): array
    {
        return ["message"];
    }

    public function toWechat(): string
    {
        $emoji = $this->priorityEmoji();
        $links = [];
        foreach ($this->actions as $key => $value) {
            $links[] = "<a href='$value'>$key</a>";
        }
        $actionRows = [];
        for ($i = 0, $n = count($links); $i < $n; $i += 2) {
            if ($i + 1 < $n) {
                $actionRows[] = $links[$i] . self::WECHAT_ACTION_SEPARATOR . $links[$i + 1];
            } else {
                $actionRows[] = $links[$i];
            }
        }
        $actionsText = implode("\n", $actionRows);
        $url = $this->viewUrl();
        $hasActions = $this->actions !== [];

        if ($hasActions) {
            $markdown = <<<EOF
# $emoji $this->title
---
$this->message
---
$actionsText
---
 <a href='$url'>查看原文</a>
EOF;
        } else {
            $markdown = <<<EOF
# $emoji $this->title
---
$this->message
 <a href='$url'>查看原文</a>
EOF;
        }

        return $this->emojiMarkdownToText($markdown);
    }

    private function priorityEmoji(): string
    {
        return self::PRIORITY_EMOJI[$this->priority] ?? self::PRIORITY_EMOJI['info'];
    }

    private function viewUrl(): string
    {
        return Context::instance()->request()->getBasicAddress() . '/' . $this->short_url;
    }

    private function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function emojiMarkdownToText($md): string
    {
        // 1. 标题 (使用极具仪式感的图标)
        $md = preg_replace('/^# (.*)$/m', '📢 【 $1 】', $md);
        $md = preg_replace('/^## (.*)$/m', '📌 $1', $md);
        $md = preg_replace('/^### (.*)$/m', '🔹 $1', $md);

        // 2. 强调 (加粗用火，斜体用闪亮)
        $md = preg_replace('/\*\*(.*?)\*\*/', '🔥$1🔥', $md);
        $md = preg_replace('/\*([^\*]+)\*/', '✨$1✨', $md);

        // 3. 列表 (使用动感图标)
        $md = preg_replace('/^\s*[\*\+-]\s+(.*)$/m', '✅ $1', $md);

        // 4. 有序列表 (使用数字表情符号替换)
        $md = preg_replace_callback('/^\s*(\d+)\.\s+(.*)$/m', function ($matches) {
            $numMap = [
                1 => '1️⃣', 2 => '2️⃣', 3 => '3️⃣', 4 => '4️⃣', 5 => '5️⃣',
                6 => '6️⃣', 7 => '7️⃣', 8 => '8️⃣', 9 => '9️⃣', 0 => '0️⃣'
            ];
            $num = $matches[1];
            $emojiNum = '';
            foreach (str_split($num) as $digit) {
                $emojiNum .= $numMap[$digit] ?? $digit;
            }
            return $emojiNum . ' ' . $matches[2];
        }, $md);

        // 5. 引用 (书本或扩音器效果)
        $md = preg_replace('/^>\s?(.*)$/m', '📖 ❝ $1 ❞', $md);

        // 6. 代码 (使用电脑或齿轮装饰)
        $md = preg_replace('/`(.*?)`/', '💻『 $1 』', $md);

        // 7. 分割线 (一串星星)
        $md = preg_replace('/^---$/m', '──────────────', $md);

        // 8. 链接与图片
        $md = preg_replace('/\!\[(.*?)\]\((.*?)\)/', '🖼️ [图片: $1]', $md);
        $md = preg_replace('/\[(.*?)\]\((.*?)\)/', '🔗 $1 ($2)', $md);

        // 9. 任务列表 (针对 [ ] 和 [x])
        $md = preg_replace('/- \[ \] (.*)/', '⬜ $1', $md);
        $md = preg_replace('/- \[x\] (.*)/', '🧡 $1', $md);

        return $md;
    }

}
