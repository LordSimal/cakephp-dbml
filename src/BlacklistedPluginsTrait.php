<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml;

use Cake\Core\Configure;

trait BlacklistedPluginsTrait
{
    /**
     * @var array
     */
    protected static array $blacklistedPlugins;

    /**
     * @return void
     */
    public static function initBacklist()
    {
        self::$blacklistedPlugins = Configure::read('Dbml.blacklistedPlugins');
    }

    /**
     * @param string $plugin The plugin name
     * @return bool
     */
    public static function isBlacklisted(string $plugin)
    {
        return in_array($plugin, self::$blacklistedPlugins, true);
    }
}
