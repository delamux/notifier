<?php
declare(strict_types=1);

/**
 * Bakkerij (https://github.com/bakkerij)
 * Copyright (c) https://github.com/bakkerij
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) https://github.com/bakkerij
 * @link          https://github.com/bakkerij Bakkerij Project
 * @since         1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Bakkerij\Notifier\Controller\Component;

use Bakkerij\Notifier\Model\Entity\Notification;
use Bakkerij\Notifier\Model\Table\NotificationsTable;
use Bakkerij\Notifier\Utility\NotificationManager;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;

/**
 * Notifier component.
 *
 * Read state is selected with explicit methods instead of a boolean/int flag:
 * - get{All,Read,UnRead}NotificationsBy()
 * - count{All,Read,Unread}Notifications()
 *
 * User ids may be integers or strings (UUID). When no user id is given, the identifier of the
 * current request identity (cakephp/authentication) is used, falling back to AuthComponent.
 */
class NotifierComponent extends Component
{
    /**
     * Default configuration.
     *
     * - UsersModel: The model where the users are stored.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'UsersModel' => 'Users',
    ];

    /**
     * @var \Cake\Controller\Controller|null
     */
    private ?Controller $Controller = null;

    /**
     * @var \Bakkerij\Notifier\Model\Table\NotificationsTable
     */
    private NotificationsTable $table;

    /**
     * @param array<string, mixed> $config Config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Controller = $this->_registry->getController();
        /** @var \Bakkerij\Notifier\Model\Table\NotificationsTable $table */
        $table = FactoryLocator::get('Table')->get('Bakkerij/Notifier.Notifications');
        $this->table = $table;
    }

    /**
     * Setter for the Controller property.
     *
     * @param \Cake\Controller\Controller $controller Controller.
     * @return void
     */
    public function setController(Controller $controller): void
    {
        $this->Controller = $controller;
    }

    /**
     * Returns one notification of the user, or null when it does not exist or belongs to
     * another user.
     *
     * @param int $notificationId Id of the notification.
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @return \Bakkerij\Notifier\Model\Entity\Notification|null
     */
    public function getNotification(int $notificationId, string|int|null $userId = null): ?Notification
    {
        /** @var \Bakkerij\Notifier\Model\Entity\Notification|null $notification */
        $notification = $this->table->find()
            ->where([
                'Notifications.id' => $notificationId,
                'Notifications.user_id' => $this->resolveUserId($userId),
            ])
            ->first();

        return $notification;
    }

    /**
     * @param string|int|null $userId Id of the user.
     * @param bool|int|null $state `true`/`1` for unread, `false`/`0` for read, `null` for all.
     * @return array<\Bakkerij\Notifier\Model\Entity\Notification>
     * @deprecated 1.3 Use getAllNotificationsBy(), getReadNotificationsBy() or
     *   getUnReadNotificationsBy() instead.
     */
    public function getNotifications(string|int|null $userId = null, bool|int|null $state = null): array
    {
        if ($state === null) {
            return $this->getAllNotificationsBy($userId);
        }

        return $state ? $this->getUnReadNotificationsBy($userId) : $this->getReadNotificationsBy($userId);
    }

    /**
     * All notifications of the user, newest first.
     *
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @param array<string, mixed> $options {
     *     @type array $whereConditions Extra conditions.
     *     @type array $order Order clause (defaults to `created DESC`).
     * }
     * @return array<\Bakkerij\Notifier\Model\Entity\Notification>
     */
    public function getAllNotificationsBy(string|int|null $userId = null, array $options = []): array
    {
        unset($options['whereConditions']['state']);

        return $this->getNotificationsFactory($userId, $options);
    }

    /**
     * Read notifications of the user, newest first.
     *
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @param array<string, mixed> $options See getAllNotificationsBy().
     * @return array<\Bakkerij\Notifier\Model\Entity\Notification>
     */
    public function getReadNotificationsBy(string|int|null $userId = null, array $options = []): array
    {
        return $this->getNotificationsFactory($userId, $this->withState($options, Notification::READ_STATUS));
    }

    /**
     * Unread notifications of the user, newest first.
     *
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @param array<string, mixed> $options See getAllNotificationsBy().
     * @return array<\Bakkerij\Notifier\Model\Entity\Notification>
     */
    public function getUnReadNotificationsBy(string|int|null $userId = null, array $options = []): array
    {
        return $this->getNotificationsFactory($userId, $this->withState($options, Notification::UNREAD_STATUS));
    }

    /**
     * Number of notifications of the user, read or not.
     *
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @return int
     */
    public function countAllNotifications(string|int|null $userId = null): int
    {
        return $this->userQuery($userId)->count();
    }

    /**
     * Number of read notifications of the user.
     *
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @return int
     */
    public function countReadNotifications(string|int|null $userId = null): int
    {
        return $this->userQuery($userId)
            ->where(['Notifications.state' => Notification::READ_STATUS])
            ->count();
    }

    /**
     * Number of unread notifications of the user.
     *
     * @param string|int|null $userId Id of the user. Defaults to the current identity.
     * @return int
     */
    public function countUnreadNotifications(string|int|null $userId = null): int
    {
        return $this->userQuery($userId)
            ->where(['Notifications.state' => Notification::UNREAD_STATUS])
            ->count();
    }

    /**
     * @param string|int|null $userId Id of the user.
     * @param bool|int|null $state `true`/`1` for unread, `false`/`0` for read, `null` for all.
     * @return int
     * @deprecated 1.3 Use countAllNotifications(), countReadNotifications() or
     *   countUnreadNotifications() instead.
     */
    public function countNotifications(string|int|null $userId = null, bool|int|null $state = null): int
    {
        if ($state === null) {
            return $this->countAllNotifications($userId);
        }

        return $state ? $this->countUnreadNotifications($userId) : $this->countReadNotifications($userId);
    }

    /**
     * Marks a notification of the user as read. Without a notification id, all unread
     * notifications of the user are marked as read.
     *
     * @param int|null $notificationId Id of the notification.
     * @param string|int|null $user Id of the user. Defaults to the current identity.
     * @return bool
     */
    public function markAsRead(?int $notificationId = null, string|int|null $user = null): bool
    {
        $conditions = ['user_id' => $this->resolveUserId($user)];
        if ($notificationId === null) {
            $conditions['state'] = Notification::UNREAD_STATUS;
        } else {
            $conditions['id'] = $notificationId;
        }

        $notifications = [];
        foreach ($this->table->find()->where($conditions) as $notification) {
            $notification->set('state', Notification::READ_STATUS);
            $notifications[] = $notification;
        }

        return (bool)$this->table->saveMany($notifications);
    }

    /**
     * Sends notifications to specific users. See NotificationManager::notify().
     *
     * @param array<string, mixed> $data Data with options.
     * @return string|false The tracking id, or false if the notifications could not be saved.
     */
    public function notify(array $data): string|false
    {
        $notification = NotificationManager::instance()->notify($data);

        if (!$notification) {
            $this->getController()->Flash->error(
                __d('bakkerij/notifier', 'An error occurred sending the notifications')
            );
        }

        return $notification;
    }

    /**
     * @param string|int|null $userId Id of the user.
     * @param array<string, mixed> $options Options (whereConditions, order).
     * @return array<\Bakkerij\Notifier\Model\Entity\Notification>
     */
    private function getNotificationsFactory(string|int|null $userId, array $options = []): array
    {
        $whereConditions = ['Notifications.user_id' => $this->resolveUserId($userId)];
        if (!empty($options['whereConditions'])) {
            $whereConditions = array_merge($whereConditions, $options['whereConditions']);
        }
        $order = $options['order'] ?? ['Notifications.created' => 'desc'];

        return $this->table->find()
            ->where($whereConditions)
            ->orderBy($order)
            ->toArray();
    }

    /**
     * @param array<string, mixed> $options Options.
     * @param int $state Notification state.
     * @return array<string, mixed>
     */
    private function withState(array $options, int $state): array
    {
        $options['whereConditions'] = array_merge($options['whereConditions'] ?? [], ['state' => $state]);

        return $options;
    }

    /**
     * @param string|int|null $userId Id of the user.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function userQuery(string|int|null $userId): SelectQuery
    {
        return $this->table->find()->where(['Notifications.user_id' => $this->resolveUserId($userId)]);
    }

    /**
     * Id of the given user, or of the current identity when none is given.
     *
     * @param string|int|null $userId Id of the user.
     * @return string|int|null
     */
    private function resolveUserId(string|int|null $userId): string|int|null
    {
        if ($userId !== null && $userId !== '') {
            return $userId;
        }

        $controller = $this->Controller ?? $this->getController();
        $identity = $controller->getRequest()->getAttribute('identity');
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            return $identity->getIdentifier();
        }

        if ($controller->components()->has('Auth')) {
            return $controller->components()->get('Auth')->user('id');
        }

        return null;
    }
}
