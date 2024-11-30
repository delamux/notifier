<?php
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
use Bakkerij\Notifier\Utility\NotificationManager;
use Cake\Controller\Component;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\TableRegistry;

/**
 * Notifier component
 */
class NotifierComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'UsersModel' => 'Users'
    ];

    /**
     * The controller.
     *
     * @var \Cake\Controller\Controller
     */
    private $Controller = null;

    /**
     * The controller.
     *
     * @var \Cake\ORM\Table
     */
    private $table = null;

    /**
     * initialize
     *
     * @param array $config Config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Controller = $this->_registry->getController();
        $this->table = TableRegistry::getTableLocator()->get('Bakkerij/Notifier.Notifications');
    }

    /**
     * setController
     *
     * Setter for the Controller property.
     *
     * @param \Cake\Controller\Controller $controller Controller.
     * @return void
     */
    public function setController($controller): void
    {
        $this->Controller = $controller;
    }

    /**
     * getNotifications
     *
     * Returns a list of notifications.
     *
     * ### Examples
     * ```
     *  // if the user is logged in, this is the way to get all notifications
     *  $this->Notifier->getNotifications();
     *
     *  // for a specific user, use the first parameter for the user_id
     *  $this->Notifier->getNotifications(1);
     *
     *  // default all notifications are returned. Use the second parameter to define read / unread:
     *
     *  // get all unread notifications
     *  $this->Notifier->getNotifications(1, true);
     *
     *  // get all read notifications
     *  $this->Notifier->getNotifications(1, false);
     * ```
     * @param int|null $userId Id of the user.
     * @param bool|null $state The state of notifications: `true` for unread, `false` for read, `null` for all.
     * @return array
     *
     * @deprecated 1.3 use getReadNotifications or getUnreadNotifications instead.
     */
    public function getNotifications($userId = null, $state = null): array
    {
        $stateCondition = [];
        if (isset($state)) {
            $stateCondition = ['whereConditions' => ['state' => $state]];
        }

        return $this->getNotificationsFactory($userId, $stateCondition);
    }


    /**
     * getNotifications
     *
     * Returns a list of notifications.
     *
     * ### Examples
     * ```
     *  // if the user is logged in, this is the way to get all notifications
     *  $this->Notifier->getNotifications();
     *
     *  // for a specific user, use the first parameter for the user_id
     *  $this->Notifier->getNotifications(1);
     *
     *
     * ```
     * @param int $userId
     * @param array $options {
     * @type array $whereConditions Conditions used for filtering.
     * @type string $order The order in which results should be returned.
     * }
     * * @return array
     */
    public function getAllNotificationsBy($userId, $options = []): array
    {
        if (array_key_exists('state', $options)) {
            unset($options['state']);
        }

        return $this->getNotificationsFactory($userId, $options);
    }

    /**
     * getNotifications
     *
     * Returns a list of notifications.
     *
     * ### Examples
     * ```
     *  // if the user is logged in, this is the way to get all notifications
     *  $this->Notifier->getNotifications();
     *
     *  // for a specific user, use the first parameter for the user_id
     *  $this->Notifier->getNotifications(1);
     *
     * // for and specific user you can add also ORM conditions for the where and order
     *
     * $this->Notifier->getNotifications(1, [whereConditions => ['']]);
     *
     * ```
     * @param int $userId
     * @param array $options {
     * @type array $whereConditions Conditions used for filtering.
     * @type string $order The order in which results should be returned.
     * }
     * * @return array
     */
    public function getReadNotificationsBy($userId, $options = []): array
    {
        $readCondition = ['whereConditions' => ['state' => Notification::READ_STATUS]];
        $conditions = array_merge($options, $readCondition);

        return $this->getNotificationsFactory($userId, $conditions);
    }

    /**
     * getNotifications
     *
     * Returns a list of notifications.
     *
     * ### Examples
     * ```
     *  // if the user is logged in, this is the way to get all notifications
     *  $this->Notifier->getNotifications();
     *
     *  // for a specific user, use the first parameter for the user_id
     *  $this->Notifier->getNotifications(1);
     *
     *
     * ```
     * @param int $userId
     * @param array $options {
     * @type array $whereConditions Conditions used for filtering.
     * @type string $order The order in which results should be returned.
     * }
     * @return array
     */
    public function getUnReadNotificationsBy($userId, $options = []): array
    {
        $unreadCondition = ['whereConditions' => ['state' => Notification::UNREAD_STATUS]];
        $conditions = array_merge($options, $unreadCondition);

        return $this->getNotificationsFactory($userId, $conditions);
    }

    /**
     * @param int $userId
     * @param array $options {
     * @type array $whereConditions Conditions used for filtering.
     * @type string $order The order in which results should be returned.
     * @type string $state The state of the items to be processed.
     * }
     * @return array
     */
    private function getNotificationsFactory($userId, $options = []): array
    {
        if (!isset($userId)) {
            $userId = $this->Controller->Auth->user('id');
        }

        $whereConditions = [
            'Notifications.user_id' => $userId,
        ];

        $order = ['created' => 'desc'];

        if (array_key_exists('whereConditions', $options)) {
            $whereConditions = array_merge($whereConditions, $options['whereConditions']);
        }

        if (array_key_exists('order', $options)) {
            $order = array_merge($whereConditions, $options['order']);
        }

        return $this->table
            ->find()
            ->where($whereConditions)
            ->order($order)
            ->toArray();
    }

    /**
     * countNotifications
     *
     * Returns a number of notifications.
     *
     * ### Examples
     * ```
     *  // if the user is logged in, this is the way to count all notifications
     *  $this->Notifier->countNotifications();
     *
     *  // for a specific user, use the first parameter for the user_id
     *  $this->Notifier->countNotifications(1);
     *
     *  // default all notifications are counted. Use the second parameter to define read / unread:
     *
     *  // count all unread notifications
     *  $this->Notifier->countNotifications(1, true);
     *
     *  // count all read notifications
     *  $this->Notifier->countNotifications(1, false);
     * ```
     * @param int|null $userId Id of the user.
     * @param bool|null $state The state of notifications: `true` for unread, `false` for read, `null` for all.
     * @return int
     */
    public function countNotifications(?int $userId = null, ?int $state = null): int
    {
        if (!$userId) {
            $userId = $this->Controller->Auth->user('id');
        }

        $query = $this->table->find()->where(['Notifications.user_id' => $userId]);

        if (!is_null($state)) {
            $query->where(['Notifications.state' => $state]);
        }

        return $query->count();
    }

    /**
     * markAsRead
     *
     * Used to mark a notification as read.
     * If no notificationId is given, all notifications of the chosen user will be marked as read.
     *
     * @param int $notificationId Id of the notification.
     * @param int|null $user Id of the user. Else the id of the session will be taken.
     * @return void|false
     */
    public function markAsRead($notificationId = null, $user = null): bool
    {
        if (!$user) {
            $user = $this->Controller->Auth->user('id');
        }

        if (!$notificationId) {
            $query = $this->table->find()->where([
                'user_id' => $user,
                'state' => Notification::UNREAD_STATUS
            ]);
        } else {
            $query = $this->table->find()->where([
                'user_id' => $user,
                'id' => $notificationId

            ]);
        }

        $notifications = [];
        foreach ($query as $notification) {
            $notification->set('state', Notification::READ_STATUS);
            $notifications[] = $notification;
        }

        $savedNotifications = $this->table->saveMany($notifications);

        if (!$savedNotifications) {
            return false;
        }

        return true;
    }

    /**
     * notify
     *
     * Sends notifications to specific users.
     * The first parameter `$data` is an array with multiple options.
     *
     * ### Options
     * - `users` - An array or int with id's of users who will receive a notification.
     * - `roles` - An array or int with id's of roles which all users ill receive a notification.
     * - `template` - The template wich will be used.
     * - `vars` - The variables used in the template.
     *
     * ### Example
     * ```
     *  NotificationManager::instance()->notify([
     *      'users' => 1,
     *      'template' => 'newOrder',
     *      'vars' => [
     *          'receiver' => $receiver->name
     *          'total' => $order->total
     *      ],
     *  ]);
     * ```
     *
     * @param array $data Data with options.
     * @return string
     */
    public function notify($data): string
    {
        $notification = NotificationManager::instance()->notify($data);
        if (!$notification) {
            $this->getController()->Flash->error(__d('bakkerij/notifier', 'An error occurred sending the notifications'));
        }

        return $notification;
    }
}
