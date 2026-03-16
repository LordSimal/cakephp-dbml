<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support\Model\Table;

use Cake\ORM\Table;

class TagsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tags');
        $this->belongsToMany('Articles', [
            'through' => 'ArticlesTags',
            'foreignKey' => 'tag_id',
            'targetForeignKey' => 'article_id',
        ]);
    }
}
