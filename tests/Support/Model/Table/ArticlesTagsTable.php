<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support\Model\Table;

use Cake\ORM\Table;

class ArticlesTagsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('articles_tags');
    }
}
