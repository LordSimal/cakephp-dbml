<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Loaders;

use Cake\Core\App;
use Cake\Core\Plugin;
use LordSimal\CakephpDbml\BlacklistedPluginsTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ModelLoader
{
    use BlacklistedPluginsTrait;

    /**
     * Get all present models in an array structure like this
     *
     * [
     *     'App' => [
     *         'ModelName1', 'ModelName2',
     *     ],
     *     'PluginName' => [
     *         'ModelName3', 'ModelName4',
     *     ]
     * ]
     *
     * @return array
     */
    public static function getModels(): array
    {
        self::initBacklist();
        $result = [
            'App' => self::getModelsForPlugin(),
        ];

        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            if (self::isBlacklisted($plugin)) {
                continue;
            }

            $pluginModels = self::getModelsForPlugin($plugin);

            foreach ($pluginModels as $model) {
                if (strpos($model, 'AppModel') !== false) {
                    continue;
                }
                $result[$plugin][] = $model;
            }
        }

        return $result;
    }

    /**
     * @param string|null $plugin The plugin name
     * @return array
     */
    protected static function getModelsForPlugin(?string $plugin = null): array
    {
        $paths = App::classPath('Model/Table', $plugin);
        $files = self::getFiles($paths);

        $models = [];
        foreach ($files as $file) {
            if (!preg_match('/^(\w+)Table\.php$/', $file, $matches)) {
                continue;
            }
            $models[] = $matches[1];
        }

        return $models;
    }

    /**
     * @param array $folders The paths to the folders to check
     * @return array
     */
    protected static function getFiles(array $folders): array
    {
        $names = [];
        foreach ($folders as $folder) {
            if (file_exists($folder)) {
                /** @var \SplFileInfo[] $files */
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($folder),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $names[] = $file->getFilename();
                    }
                }
            }
        }

        return $names;
    }
}
