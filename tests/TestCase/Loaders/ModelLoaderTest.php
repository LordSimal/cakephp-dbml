<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\TestCase\Loaders;

use Cake\TestSuite\TestCase;
use LordSimal\CakephpDbml\Loaders\ModelLoader;
use LordSimal\CakephpDbml\Test\Support\DbmlTestTrait;

class ModelLoaderTest extends TestCase
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

    public function testGetModelsReturnsAppAndPluginModelsAndSkipsBlacklistedPlugins(): void
    {
        $this->createAppModelFile('Articles');
        $this->createAppModelFile('Users');
        $this->createPluginModelFile('TestPlugin', 'Comments');
        $this->createPluginModelFile('TestPlugin', 'AppModel');
        $this->createPluginModelFile('DebugKit', 'Panels');

        $result = ModelLoader::getModels();

        $this->assertSame(['Articles', 'Users'], $result['App']);
        $this->assertSame(['Comments'], $result['TestPlugin']);
        $this->assertArrayNotHasKey('DebugKit', $result);
    }
}
