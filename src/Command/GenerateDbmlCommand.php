<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
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
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

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

        foreach ($result as $plugin => $pluginData) :
            foreach ($pluginData as $model => $modelData) :
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
