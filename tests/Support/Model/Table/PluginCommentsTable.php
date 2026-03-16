<?php
declare(strict_types=1);

namespace LordSimal\CakephpDbml\Test\Support\Model\Table;

use Cake\ORM\Table;

class PluginCommentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('plugin_comments');
        $this->belongsTo('Articles', [
            'foreignKey' => 'article_id',
        ]);
    }
}
