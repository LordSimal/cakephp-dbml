<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml;

use Cake\Core\Configure;

class DbmlWriter
{
    /**
     * The path where the file is being written to.
     * Defined by the config keys
     * - 'Dbml.path'
     * - 'Dbml.filename'
     *
     * @var string
     */
    private string $path;

    /**
     * Initialize
     */
    public function __construct()
    {
        $path = Configure::read('Dbml.path');
        $filename = Configure::read('Dbml.filename');
        $this->path = $path . $filename;
        if (!is_writable($path)) {
            throw new \Exception('Filepath "' . $path . '" is not writable!');
        }
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    /**
     * @param array $result Write the result from $dbmlFormatter->format() to the configured path
     * @return void
     */
    public function write(array $result)
    {
        foreach ($result as $tableName => $tableData) :
            $this->append(sprintf('Table "%s" {', $tableName));
            foreach ($tableData['columns'] as $column) :
                if (isset($column['additional'])) {
                    $this->append(
                        sprintf(
                            '    "%s" %s [%s]',
                            $column['name'],
                            $column['type'],
                            implode(' ', $column['additional'])
                        )
                    );
                } else {
                    $this->append(sprintf('    "%s" %s', $column['name'], $column['type']));
                }
            endforeach;

            if (isset($tableData['isJunctionTable']) && $tableData['isJunctionTable'] === true) {
                $this->append();
                $this->append('    Indexes {');
                $this->append(sprintf('        (%s) [pk]', implode(', ', $tableData['indexes'])));
                $this->append('    }');
            }

            $this->append('}');

            foreach ($tableData['associations'] as $association) :
                $this->append(
                    sprintf('Ref: %s.%s > %s.%s', $association[0], $association[1], $association[2], $association[3])
                );
            endforeach;

            $this->append();
        endforeach;
    }

    /**
     * Append the given text to the DBML file
     *
     * @param string $text The text to append
     * @param bool $newLine Should a new line be added at the end
     * @return void
     */
    private function append(string $text = '', bool $newLine = true)
    {
        if ($newLine) {
            $text .= PHP_EOL;
        }
        file_put_contents($this->path, $text, FILE_APPEND);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
