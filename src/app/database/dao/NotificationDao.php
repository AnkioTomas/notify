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

    private function ensureShortUrl(): string
    {
        $shortUrl = substr(md5(uuid()), 8, 6);
        if ($this->getByShortUrl($shortUrl) !== null) {
            return $this->ensureShortUrl();
        }
        return $shortUrl;
    }
}
