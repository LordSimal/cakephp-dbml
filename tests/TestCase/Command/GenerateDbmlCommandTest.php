<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\TestCase\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use LordSimal\CakephpDbml\Command\GenerateDbmlCommand;
use LordSimal\CakephpDbml\Test\Support\DbmlTestTrait;

class GenerateDbmlCommandTest extends TestCase
{
    use DbmlTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDbmlTestEnvironment();
        $this->createOrmSchema();
        $this->configureTableLocator();

        $this->createAppModelFile('Articles');
        $this->createAppModelFile('Comments');
        $this->createAppModelFile('Tags');
    }

    protected function tearDown(): void
    {
        $this->tearDownDbmlTestEnvironment();
        parent::tearDown();
    }

    public function testExecuteWritesDbmlFileAndReportsSuccess(): void
    {
        $stdout = new StubConsoleOutput();
        $stderr = new StubConsoleOutput();
        $io = new ConsoleIo($stdout, $stderr);
        $io->level(ConsoleIo::VERBOSE);

        $command = new GenerateDbmlCommand();
        $command->execute(new Arguments([], [], []), $io);

        $path = $this->tempPath . 'dbml-export.dbml';
        $contents = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertIsString($contents);
        $this->assertStringContainsString('DBML file written to ' . $path, $stdout->output());
        $this->assertStringContainsString('Checking: Articles (table articles)', $stdout->output());
        $this->assertStringContainsString('Table "articles" {', $contents);
        $this->assertStringContainsString('Table "articles_tags" {', $contents);
        $this->assertStringContainsString('Ref: comments.article_id > articles.id', $contents);
        $this->assertStringContainsString('Ref: articles_tags.tag_id > tags.id', $contents);
        $this->assertSame('', $stderr->output());
    }
}
