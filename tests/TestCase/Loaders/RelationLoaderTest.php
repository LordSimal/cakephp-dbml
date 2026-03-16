<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\TestCase\Loaders;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use LordSimal\CakephpDbml\Loaders\RelationLoader;
use LordSimal\CakephpDbml\Test\Support\DbmlTestTrait;

class RelationLoaderTest extends TestCase
{
    use DbmlTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDbmlTestEnvironment();
        $this->createOrmSchema();
        $this->configureTableLocator();
    }

    protected function tearDown(): void
    {
        $this->tearDownDbmlTestEnvironment();
        parent::tearDown();
    }

    public function testGetRelationsReturnsSchemasForAppAndPluginModels(): void
    {
        $stdout = new StubConsoleOutput();
        $stderr = new StubConsoleOutput();
        $io = new ConsoleIo($stdout, $stderr);
        $io->level(ConsoleIo::VERBOSE);

        $loader = new RelationLoader($io);
        $result = $loader->getRelations([
            'App' => ['Articles'],
            'TestPlugin' => ['Comments'],
        ]);

        $this->assertSame('articles', $result['App']['Articles']['schema']->name());
        $this->assertArrayHasKey('oneToMany', $result['App']['Articles']['relations']);
        $this->assertArrayHasKey('manyToMany', $result['App']['Articles']['relations']);
        $this->assertSame('plugin_comments', $result['TestPlugin']['Comments']['schema']->name());
        $this->assertArrayHasKey('manyToOne', $result['TestPlugin']['Comments']['relations']);
        $this->assertStringContainsString('Checking: Articles (table articles)', $stdout->output());
        $this->assertStringContainsString('Relation detected: Comments manyToOne Articles', $stdout->output());
    }

    public function testGetRelationsWarnsWhenTableSchemaIsMissing(): void
    {
        $stdout = new StubConsoleOutput();
        $stderr = new StubConsoleOutput();
        $io = new ConsoleIo($stdout, $stderr);

        $loader = new RelationLoader($io);
        $result = $loader->getRelations([
            'App' => ['MissingTable'],
        ]);

        $this->assertSame([], $result);
        $this->assertStringContainsString('Table for MissingTable (missing_tables) from Plugin "App" is not present', $stderr->output());
    }
}
