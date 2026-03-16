<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use LordSimal\CakephpDbml\DbmlFormatter;
use LordSimal\CakephpDbml\DbmlWriter;
use LordSimal\CakephpDbml\Loaders\ModelLoader;
use LordSimal\CakephpDbml\Loaders\RelationLoader;

/**
 * FillVhostHttpCodeCache command.
 */
class GenerateDbmlCommand extends Command
{
    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $models = ModelLoader::getModels();

        $relationLoader = new RelationLoader($io);
        $result = $relationLoader->getRelations($models);

        $dbmlFormatter = new DbmlFormatter();

        foreach ($result as $pluginData) :
            foreach ($pluginData as $modelData) :
                if (isset($modelData['schema'])) {
                    $dbmlFormatter->setSchema($modelData['schema']);

                    if (isset($modelData['relations'])) {
                        $dbmlFormatter->setRelations($modelData['relations']);
                    } else {
                        $dbmlFormatter->setRelations([]);
                    }

                    $result = $dbmlFormatter->format();
                }
            endforeach;
        endforeach;

        // Sort by table names
        ksort($result);

        $dbmlWriter = new DbmlWriter();
        $dbmlWriter->write($result);

        $io->success('DBML file written to ' . $dbmlWriter->getPath());
    }
}
