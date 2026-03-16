<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Exception;
use LordSimal\CakephpDbml\DbmlWriter;
use LordSimal\CakephpDbml\Test\Support\DbmlTestTrait;

class DbmlWriterTest extends TestCase
{
    use DbmlTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDbmlTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->tearDownDbmlTestEnvironment();
        parent::tearDown();
    }

    public function testConstructThrowsExceptionForNonWritablePath(): void
    {
        Configure::write('Dbml.path', $this->tempPath . 'missing' . DS);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('is not writable');

        new DbmlWriter();
    }

    public function testWriteCreatesDbmlFile(): void
    {
        $writer = new DbmlWriter();
        $writer->write([
            'articles' => [
                'columns' => [
                    ['name' => 'id', 'type' => 'integer', 'additional' => ['primary key']],
                    ['name' => 'title', 'type' => 'varchar(255)', 'additional' => ['not null']],
                ],
                'associations' => [
                    ['comments', 'article_id', 'articles', 'id'],
                ],
            ],
            'articles_tags' => [
                'columns' => [
                    ['name' => 'article_id', 'type' => 'integer', 'additional' => ['not null']],
                    ['name' => 'tag_id', 'type' => 'integer', 'additional' => ['not null']],
                ],
                'associations' => [
                    ['articles_tags', 'article_id', 'articles', 'id'],
                    ['articles_tags', 'tag_id', 'tags', 'id'],
                ],
                'isJunctionTable' => true,
                'indexes' => ['article_id', 'tag_id'],
            ],
        ]);

        $contents = file_get_contents($writer->getPath());

        $this->assertIsString($contents);
        $this->assertStringContainsString('Table "articles" {', $contents);
        $this->assertStringContainsString('"id" integer [primary key]', $contents);
        $this->assertStringContainsString('Ref: comments.article_id > articles.id', $contents);
        $this->assertStringContainsString('Indexes {', $contents);
        $this->assertStringContainsString('(article_id, tag_id) [pk]', $contents);
    }
}
