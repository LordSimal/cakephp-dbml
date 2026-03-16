<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support\Model\Table;

use Cake\ORM\Table;

class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('comments');
        $this->belongsTo('Articles', [
            'foreignKey' => 'article_id',
        ]);
    }
}
