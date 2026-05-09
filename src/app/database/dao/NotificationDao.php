<?php

declare(strict_types=1);

namespace app\database\dao;

use app\database\model\NotificationModel;

use function nova\framework\uuid;

use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    /**
     * 发布通知（DTO 风格）
     */
    public function post(NotificationModel $notificationModel): NotificationModel
    {
        $notificationModel->short_url = $this->ensureShortUrl();
        $notificationModel->id = $this->insertModel($notificationModel);

        $this->delete()->where(["t < ".(time() - 3600 * 24 * 7)])->commit();

        return $notificationModel;
    }

    public function getByShortUrl(string $url): ?NotificationModel
    {
        return $this->find(null, ['short_url' => $url]);
    }

    /**
     * 后台分页列表（按发布时间倒序）
     *
     * @return array{total: int, data: NotificationModel[]}
     */
    public function paginateLatest(int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        $result = $this->getAll([], [], $page, $pageSize, 't');

        return [
            'total' => (int)($result['total'] ?? 0),
            'data'  => $result['data'] ?? [],
        ];
    }

    private function ensureShortUrl(): string
    {
        $shortUrl = substr(md5(uuid()), 8, 6);
        if ($this->getByShortUrl($shortUrl) !== null) {
            return $this->ensureShortUrl();
        }
        return $shortUrl;
    }

    public function removeByApp(int $app): void
    {
        $this->delete()->where(["app" => $app])->commit();
    }
}
