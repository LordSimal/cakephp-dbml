<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml;

use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;

class DbmlFormatter
{
    /**
     * The schema we are currently working with
     *
     * @var \Cake\Database\Schema\TableSchemaInterface
     */
    protected TableSchemaInterface $schema;

    /**
     * The realtions associated to the current active schema
     *
     * @var array
     */
    protected array $relations;

    /**
     * Which tables should not be generated to DBML
     *
     * @var array
     */
    private array $blacklistedTables;

    /**
     * @var array
     */
    private array $assocsWritten;

    /**
     * @var array
     */
    private array $result;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->blacklistedTables = Configure::read('Dbml.blacklistedTables');
        $this->result = [];
        $this->assocsWritten = [];
    }

    /**
     * @return array
     */
    public function format()
    {
        $inBlacklistedTables = in_array($this->schema->name(), $this->blacklistedTables, true);
        $isAlreadyProcessed = isset($this->result[$this->schema->name()]);

        if (!$inBlacklistedTables) :
            // only add table if we have not already processed it through another relation
            if (!$isAlreadyProcessed) :
                $this->addTable();
            endif;

            // Always add Relationships
            if ($this->relations) :
                foreach ($this->relations as $relationType => $relations) :
                    if ($relationType === 'oneToMany') {
                        $this->addAssoc($relations);
                    } elseif ($relationType === 'manyToOne') {
                        $this->addAssoc($relations, true);
                    } elseif ($relationType === 'manyToMany') {
                        $this->addManyToMany($relations);
                    }
                endforeach;
            endif;
        endif;

        return $this->result;
    }

    /**
     * @return void
     */
    private function addTable()
    {
        $this->result[$this->schema->name()] = [];
        $this->result[$this->schema->name()]['columns'] = [];
        $this->result[$this->schema->name()]['associations'] = [];

        // Add Columns
        foreach ($this->schema->columns() as $column) :
            $columnSchema = $this->schema->getColumn($column);

            // TODO: Maybe more logic for other column types necessary?
            if ($columnSchema['type'] === 'string') {
                $columnData = [
                    'name' => $column,
                    'type' => 'varchar(' . $columnSchema['length'] . ')',
                ];
            } else {
                $columnData = [
                    'name' => $column,
                    'type' => $columnSchema['type'],
                ];
            }

            if ($this->isPK($column)) {
                $columnData['additional'][] = 'primary key';
            } elseif ($columnSchema['null'] === false) {
                $columnData['additional'][] = 'not null';
            }

            $this->result[$this->schema->name()]['columns'][] = $columnData;
        endforeach;
    }

    /**
     * @param string $column Is the given column name a primary key in the current schema?
     * @return bool
     */
    private function isPK(string $column)
    {
        $pk = $this->schema->getPrimaryKey();

        return count($pk) === 1 && $column === $pk[0];
    }

    /**
     * @param array $relations array of relations
     * @param bool $isManyToOne Is this a manyToOne relation type?
     * @return void
     */
    private function addAssoc(array $relations, bool $isManyToOne = false)
    {
        /** @var \Cake\ORM\Association $relation */
        foreach ($relations as $relation) {
            $tableName = $this->schema->name();
            $fkTable = $relation->getTarget()
                ->getTable();

            if (in_array($fkTable, $this->blacklistedTables, true)) {
                continue;
            }

            $fk = $relation->getForeignKey();
            $bk = $relation->getBindingKey();

            if ($isManyToOne) {
                $tmp = $tableName;
                $tableName = $fkTable;
                $fkTable = $tmp;
            }
            $association = [$fkTable, $fk, $tableName, $bk];
            $implodedAssoc = implode('|', $association);
            if (!in_array($implodedAssoc, $this->assocsWritten, true)) {
                $this->assocsWritten[] = $implodedAssoc;
                $this->result[$this->schema->name()]['associations'][] = $association;
            }
        }
    }

    /**
     * Since `belongsToMany()` associations contain the junction table
     * we have to process them separately because they are not present in the initial
     * `$relationLoader->getRelations($models);` run executed in the command
     *
     * @param array $relations array of relations
     * @return void
     */
    private function addManyToMany(array $relations)
    {
        /** @var \Cake\ORM\Association\BelongsToMany $relation */
        foreach ($relations as $relation) {
            $sourceFK = $relation->getForeignKey();
            $targetFK = $relation->getTargetForeignKey();

            $junctionTable = $relation->junction();
            $junctionTableName = $junctionTable->getTable();

            $junctionTableSchema = $junctionTable->getSchema();
            $inBlacklistedTables = in_array($junctionTableName, $this->blacklistedTables, true);

            if (!$inBlacklistedTables) :
                if (!isset($this->result[$junctionTableSchema->name()])) {
                    $this->result[$junctionTableSchema->name()] = [];
                }
                $this->result[$junctionTableSchema->name()] += [
                    'columns' => [],
                    'associations' => [],
                    'isJunctionTable' => true,
                    'indexes' => [],
                ];

                // Add Columns
                foreach ($junctionTableSchema->columns() as $column) :
                    $columnSchema = $junctionTableSchema->getColumn($column);

                    // TODO: Maybe more logic for other column types necessary?
                    if ($columnSchema['type'] === 'string') {
                        $columnData = [
                            'name' => $column,
                            'type' => 'varchar(' . $columnSchema['length'] . ')',
                        ];
                    } else {
                        $columnData = [
                            'name' => $column,
                            'type' => $columnSchema['type'],
                        ];
                    }

                    if ($this->isPK($column)) {
                        $columnData['additional'][] = 'primary key';
                    } elseif ($columnSchema['null'] === false) {
                        $columnData['additional'][] = 'not null';
                    }

                    if (!in_array($columnData, $this->result[$junctionTableSchema->name()]['columns'], true)) {
                        $this->result[$junctionTableSchema->name()]['columns'][] = $columnData;
                    }

                    if (!in_array($column, $this->result[$junctionTableSchema->name()]['indexes'], true)) {
                        $this->result[$junctionTableSchema->name()]['indexes'][] = $column;
                    }
                endforeach;

                $sourceTableName = $relation->getSource()->getTable();
                $sourceBK = $relation->getBindingKey();
                $targetTableName = $relation->getTarget()->getTable();
                $targetBK = $relation->getTarget()->getPrimaryKey();

                // Init array
                if (!isset($this->result[$junctionTableSchema->name()]['associations'])) {
                    $this->result[$junctionTableSchema->name()]['associations'] = [];
                }

                // Add Refs between all 3 tables
                $sourceAssoc = [$junctionTableName, $sourceFK, $sourceTableName, $sourceBK];
                $implodedSourceAssoc = implode('|', $sourceAssoc);
                if (!in_array($implodedSourceAssoc, $this->assocsWritten, true)) {
                    $this->assocsWritten[] = $implodedSourceAssoc;
                    $this->result[$junctionTableSchema->name()]['associations'][] = $sourceAssoc;
                }

                $targetAssoc = [$junctionTableName, $targetFK, $targetTableName, $targetBK];
                $implodedTargetAssoc = implode('|', $targetAssoc);
                if (!in_array($implodedTargetAssoc, $this->assocsWritten, true)) {
                    $this->assocsWritten[] = $implodedTargetAssoc;
                    $this->result[$junctionTableSchema->name()]['associations'][] = $targetAssoc;
                }
            endif;
        }
    }

    /**
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    public function getSchema(): TableSchemaInterface
    {
        return $this->schema;
    }

    /**
     * @param \Cake\Database\Schema\TableSchemaInterface $schema The current schema
     */
    public function setSchema(TableSchemaInterface $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param array $relations The relations
     */
    public function setRelations(array $relations): void
    {
        $this->relations = $relations;
    }
}
