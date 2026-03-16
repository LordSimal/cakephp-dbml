<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support\Model\Table;

use Cake\ORM\Table;

class MissingTableTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('missing_tables');
    }
}
