<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for the Notifier plugin (CakePHP 4.5).
 */

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;

$findRoot = function (string $root): string {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new RuntimeException('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require_once $root . '/vendor/autoload.php';

define('ROOT', $root . DIRECTORY_SEPARATOR);
define('APP_DIR', 'TestApp');
define('APP', ROOT . 'tests' . DIRECTORY_SEPARATOR . 'TestApp' . DIRECTORY_SEPARATOR);
define('TMP', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'notifier-tests' . DIRECTORY_SEPARATOR);
define('CACHE', TMP . 'cache' . DIRECTORY_SEPARATOR);
define('LOGS', TMP . 'logs' . DIRECTORY_SEPARATOR);
define('CONFIG', ROOT . 'config' . DIRECTORY_SEPARATOR);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'cakephp' . DIRECTORY_SEPARATOR . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DIRECTORY_SEPARATOR);
define('CAKE', CORE_PATH . 'src' . DIRECTORY_SEPARATOR);

require_once CORE_PATH . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

Configure::write('debug', true);
Configure::write('App', ['namespace' => 'TestApp', 'encoding' => 'UTF-8', 'paths' => ['templates' => [], 'locales' => []]]);

\Cake\Cache\Cache::setConfig([
    '_cake_translations_' => ['className' => \Cake\Cache\Engine\NullEngine::class],
    '_cake_model_' => ['className' => \Cake\Cache\Engine\NullEngine::class],
]);

if (!getenv('DB_URL')) {
    putenv('DB_URL=sqlite:///:memory:');
}
ConnectionManager::setConfig('test', ['url' => getenv('DB_URL'), 'timezone' => 'UTC']);

Plugin::getCollection()->add(new \Cake\Core\BasePlugin([
    'name' => 'Bakkerij/Notifier',
    'path' => ROOT,
]));

(new SchemaLoader())->loadInternalFile(__DIR__ . DIRECTORY_SEPARATOR . 'schema.php', 'test');
