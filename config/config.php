<?php

return [
    'Dbml' => [
        'path' => ROOT . DS,
        'filename' => 'dbml-export.dbml',
        'blacklistedPlugins' => [
            'DebugKit',
            'Migrations',
        ],
        'blacklistedTables' => []
    ]
];
