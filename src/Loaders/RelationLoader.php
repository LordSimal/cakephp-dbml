<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Loaders;

use Cake\Console\ConsoleIo;
use Cake\Database\Exception\DatabaseException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Locator\LocatorInterface;

class RelationLoader
{
    use LocatorAwareTrait;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    private ConsoleIo $io;

    /**
     * @var \Cake\ORM\Locator\LocatorInterface
     */
    private LocatorInterface $tableLocator;

    /**
     * Initialize the tablelocator
     *
     * @param \Cake\Console\ConsoleIo $io The IO object from the command
     */
    public function __construct(ConsoleIo $io)
    {
        $this->io = $io;
        $this->tableLocator = $this->getTableLocator();
    }

    /**
     * Get the list of relations for given models
     *
     * @param array $modelsList List of models by module (apps, plugins, etc)
     * @return array
     */
    public function getRelations(array $modelsList): array
    {
        $result = [];

        foreach ($modelsList as $plugin => $models) :
            foreach ($models as $model) :
                if ($plugin === 'App') {
                    $modelInstance = $this->tableLocator->get($model);
                } else {
                    $modelInstance = $this->tableLocator->get($plugin . '.' . $model);
                }

                $this->io->out('Checking: ' . $model . ' (table ' . $modelInstance->getTable() . ')', 1, ConsoleIo::VERBOSE);

                /** @var \Cake\ORM\Association[] $associations */
                $associations = $modelInstance->associations();
                foreach ($associations as $association) {
                    $relationType = $association->type();
                    $relationModel = $association->getAlias();
                    $this->io->out(' - Relation detected: ' . $model . ' ' . $relationType . ' ' . $relationModel, 1, ConsoleIo::VERBOSE);

                    $result[$plugin][$model]['relations'][$relationType][] = $association;
                }

                try {
                    $result[$plugin][$model]['schema'] = $modelInstance->getSchema();
                } catch (DatabaseException $e) {
                    $this->io->warning('Table for ' . $model . ' (' . $modelInstance->getTable() . ') from Plugin "' . $plugin . '" is not present');
                }
            endforeach;
        endforeach;

        return $result;
    }
}
