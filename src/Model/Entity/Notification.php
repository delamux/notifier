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

namespace Bakkerij\Notifier\Model\Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\Utility\Text;

/**
 * Notification Entity.
 */
class Notification extends Entity
{

    const UNREAD_STATUS = 1;
    const READ_STATUS = 0;
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'template' => true,
        'vars' => true,
        'tracking_id' => true,
        'user_id' => true,
        'state' => false,
        'user' => false,
    ];

    /**
     * _getVars
     *
     * Getter for the vars-column.
     *
     * @param string $vars Data.
     * @return mixed
     */
    protected function _getVars($vars): array|string|null
    {
        $array = json_decode($vars, true);

        if (is_object($array)) {
            return $array;
        }

        return $vars;
    }

    /**
     * _setVars
     *
     * Setter for the vars-column
     *
     * @param array $vars Data.
     * @return string
     */
    protected function _setVars($vars): string|null
    {
        if (is_array($vars)) {
            return json_encode($vars);
        }

        return $vars;
    }

    /**
     * _getTitle
     *
     * Getter for the title.
     * Data is used from the vars-column.
     * The template is used from the configurations.
     *
     * @return string
     */
    protected function _getTitle(): string
    {
        $templates = Configure::read('Notifier.templates');

        if (array_key_exists($this->get('template'), $templates)) {
            $template = $templates[$this->get('template')];

            $vars = json_decode($this->get('vars'), true);

            return Text::insert($template['title'], $vars);
        }
        return '';
    }

    /**
     * _getBody
     *
     * Getter for the body.
     * Data is used from the vars-column.
     * The template is used from the configurations.
     *
     * @return string
     */
    protected function _getBody(): string
    {
        $templates = Configure::read('Notifier.templates');

        if (array_key_exists($this->get('template'), $templates)) {
            $template = $templates[$this->get('template')];

            $vars = json_decode($this->get('vars'), true);

            return Text::insert($template['body'], $vars);
        }
        return '';
    }

    /**
     * _getUnread
     *
     * Boolean if the notification is read or not.
     *
     * @return bool
     */
    protected function _getUnread(): bool
    {
        return $this->get('state') == self::UNREAD_STATUS;
    }

    /**
     * _getRead
     *
     * Boolean if the notification is read or not.
     *
     * @return bool
     */
    protected function _getRead(): bool
    {
        return $this->get('state') == self::READ_STATUS;
    }

    /**
     * Virtual fields
     *
     * @var array
     */
    protected $_virtual = ['title', 'body', 'unread', 'read'];
}
