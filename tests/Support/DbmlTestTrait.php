<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginCollection;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\TableLocator;

trait DbmlTestTrait
{
    protected string $tempPath;

    /**
     * @var array<string>
     */
    protected array $createdPaths = [];

    protected function setUpDbmlTestEnvironment(): void
    {
        $this->tempPath = sys_get_temp_dir() . DS . 'cakephp-dbml-tests' . DS . uniqid('', true) . DS;
        $this->createDirectory($this->tempPath);

        Configure::write('Dbml.path', $this->tempPath);
        Configure::write('Dbml.filename', 'dbml-export.dbml');
        Configure::write('Dbml.blacklistedPlugins', ['DebugKit', 'Migrations']);
        Configure::write('Dbml.blacklistedTables', []);

        ConnectionManager::alias('test', 'default');
        $this->resetPluginCollection();
        FactoryLocator::drop('Table');
        FactoryLocator::add('Table', new TableLocator());
    }

    protected function tearDownDbmlTestEnvironment(): void
    {
        ConnectionManager::dropAlias('default');
        $this->resetPluginCollection();
        FactoryLocator::drop('Table');
        FactoryLocator::add('Table', new TableLocator());

        foreach (array_reverse($this->createdPaths) as $path) {
            $this->deletePath($path);
        }
    }

    protected function configureTableLocator(): TableLocator
    {
        $locator = new TableLocator();
        FactoryLocator::drop('Table');
        FactoryLocator::add('Table', $locator);

        $locator->setConfig('Articles', ['className' => Model\Table\ArticlesTable::class]);
        $locator->setConfig('Comments', ['className' => Model\Table\CommentsTable::class]);
        $locator->setConfig('Tags', ['className' => Model\Table\TagsTable::class]);
        $locator->setConfig('ArticlesTags', ['className' => Model\Table\ArticlesTagsTable::class]);
        $locator->setConfig('MissingTable', ['className' => Model\Table\MissingTableTable::class]);
        $locator->setConfig('TestPlugin.Comments', ['className' => Model\Table\PluginCommentsTable::class]);

        return $locator;
    }

    protected function resetPluginCollection(): void
    {
        if (method_exists(Plugin::class, 'setCollection')) {
            Plugin::setCollection(new PluginCollection());

            return;
        }

        Plugin::getCollection()->clear();
    }

    protected function createOrmSchema(): void
    {
        $connection = $this->getTestConnection();
        $connection->execute('DROP TABLE IF EXISTS plugin_comments');
        $connection->execute('DROP TABLE IF EXISTS articles_tags');
        $connection->execute('DROP TABLE IF EXISTS comments');
        $connection->execute('DROP TABLE IF EXISTS tags');
        $connection->execute('DROP TABLE IF EXISTS articles');

        $connection->execute('CREATE TABLE articles (id INTEGER PRIMARY KEY, title VARCHAR(255) NOT NULL, body TEXT NULL)');
        $connection->execute('CREATE TABLE comments (id INTEGER PRIMARY KEY, article_id INTEGER NOT NULL, body TEXT NULL)');
        $connection->execute('CREATE TABLE tags (id INTEGER PRIMARY KEY, name VARCHAR(100) NOT NULL)');
        $connection->execute('CREATE TABLE articles_tags (article_id INTEGER NOT NULL, tag_id INTEGER NOT NULL)');
        $connection->execute('CREATE TABLE plugin_comments (id INTEGER PRIMARY KEY, article_id INTEGER NOT NULL, body TEXT NULL)');
    }

    protected function createAppModelFile(string $model): void
    {
        $directory = APP . 'Model' . DS . 'Table' . DS;
        $this->createDirectory($directory);

        $path = $directory . $model . 'Table.php';
        file_put_contents($path, "<?php\n");
        $this->createdPaths[] = $path;
    }

    protected function createPluginModelFile(string $plugin, string $model): string
    {
        $pluginPath = $this->tempPath . 'plugins' . DS . $plugin . DS;
        $classPath = $pluginPath . 'src' . DS;
        $directory = $classPath . 'Model' . DS . 'Table' . DS;

        $this->createDirectory($directory);
        $path = $directory . $model . 'Table.php';
        file_put_contents($path, "<?php\n");
        $this->createdPaths[] = $path;

        $collection = Plugin::getCollection();
        if (!$collection->has($plugin)) {
            $collection->add(new BasePlugin([
                'name' => $plugin,
                'path' => $pluginPath,
                'classPath' => $classPath,
            ]));
        }

        return $path;
    }

    protected function createDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $this->createdPaths[] = $path;
    }

    protected function getTestConnection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('test');

        return $connection;
    }

    protected function deletePath(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->deletePath($path . DS . $entry);
        }

        @rmdir($path);
    }
}
