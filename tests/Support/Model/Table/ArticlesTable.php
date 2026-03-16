<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support\Model\Table;

use Cake\ORM\Table;

class ArticlesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('articles');
        $this->hasMany('Comments', [
            'foreignKey' => 'article_id',
        ]);
        $this->belongsToMany('Tags', [
            'through' => 'ArticlesTags',
            'foreignKey' => 'article_id',
            'targetForeignKey' => 'tag_id',
        ]);
    }
}
