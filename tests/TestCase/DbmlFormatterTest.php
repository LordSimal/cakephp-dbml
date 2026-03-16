<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\TestCase;

use Cake\Core\Configure;
use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\TestCase;
use LordSimal\CakephpDbml\DbmlFormatter;
use LordSimal\CakephpDbml\Test\Support\DbmlTestTrait;

class DbmlFormatterTest extends TestCase
{
    use DbmlTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDbmlTestEnvironment();
        $this->createOrmSchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownDbmlTestEnvironment();
        parent::tearDown();
    }

    public function testFormatBuildsColumnsAndDeduplicatesReverseAssociations(): void
    {
        $locator = $this->configureTableLocator();
        $articles = $locator->get('Articles');
        $comments = $locator->get('Comments');

        $formatter = new DbmlFormatter();
        $formatter->setSchema($articles->getSchema());
        $formatter->setRelations([
            'oneToMany' => [$articles->associations()->get('Comments')],
        ]);
        $result = $formatter->format();

        $formatter->setSchema($comments->getSchema());
        $formatter->setRelations([
            'manyToOne' => [$comments->associations()->get('Articles')],
        ]);
        $result = $formatter->format();

        $this->assertSame('integer', $result['articles']['columns'][0]['type']);
        $this->assertSame(['primary key'], $result['articles']['columns'][0]['additional']);
        $this->assertSame('varchar(255)', $result['articles']['columns'][1]['type']);
        $this->assertSame(['not null'], $result['articles']['columns'][1]['additional']);
        $this->assertCount(1, $result['articles']['associations']);
        $this->assertSame(['comments', 'article_id', 'articles', 'id'], $result['articles']['associations'][0]);
        $this->assertSame([], $result['comments']['associations']);
    }

    public function testFormatSkipsBlacklistedTable(): void
    {
        Configure::write('Dbml.blacklistedTables', ['articles']);

        $schema = (new TableSchema('articles'))
            ->addColumn('id', ['type' => 'integer', 'null' => false])
            ->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);

        $formatter = new DbmlFormatter();
        $formatter->setSchema($schema);
        $formatter->setRelations([]);

        $this->assertSame([], $formatter->format());
    }

    public function testFormatCreatesJunctionTableForManyToManyRelations(): void
    {
        $locator = $this->configureTableLocator();
        $articles = $locator->get('Articles');

        $formatter = new DbmlFormatter();
        $formatter->setSchema($articles->getSchema());
        $formatter->setRelations([
            'manyToMany' => [$articles->associations()->get('Tags')],
        ]);
        $result = $formatter->format();

        $this->assertArrayHasKey('articles_tags', $result);
        $this->assertTrue($result['articles_tags']['isJunctionTable']);
        $this->assertSame(['article_id', 'tag_id'], $result['articles_tags']['indexes']);
        $this->assertSame(['articles_tags', 'article_id', 'articles', 'id'], $result['articles_tags']['associations'][0]);
        $this->assertSame(['articles_tags', 'tag_id', 'tags', 'id'], $result['articles_tags']['associations'][1]);
    }
}
