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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bakkerij\Notifier;

use Cake\Core\BasePlugin;

/**
 * Plugin class. Loading a plugin without one is deprecated since CakePHP 5.3.
 */
class NotifierPlugin extends BasePlugin
{
    /**
     * The plugin has no routes, middleware, console commands or services.
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * @var bool
     */
    protected bool $servicesEnabled = false;
}
