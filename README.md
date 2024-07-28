
# CakePHP 4 DBML plugin

[![Latest Stable Version](https://poser.pugx.org/lordsimal/cakephp-dbml/v)](https://packagist.org/packages/lordsimal/cakephp-dbml) [![Total Downloads](https://poser.pugx.org/lordsimal/cakephp-dbml/downloads)](https://packagist.org/packages/lordsimal/cakephp-dbml) [![Latest Unstable Version](https://poser.pugx.org/lordsimal/cakephp-dbml/v/unstable)](https://packagist.org/packages/lordsimal/cakephp-dbml) [![License](https://poser.pugx.org/lordsimal/cakephp-dbml/license)](https://packagist.org/packages/lordsimal/cakephp-dbml) [![PHP Version Require](https://poser.pugx.org/lordsimal/cakephp-dbml/require/php)](https://packagist.org/packages/lordsimal/cakephp-dbml)

This plugin generates a DBML file from your current present CakePHP table files including plugin ones.

See https://www.dbml.org/, https://dbdiagram.io and https://dbdocs.io/ to get more information about DBML.


## Installation

The recommended way to install this plugin via [composer](https://getcomposer.org) is:

```
composer require lordsimal/cakephp-dbml --dev
```

Then execute

```
bin/cake plugin load LordSimal/CakephpDbml
```

**or** add this to your `src/Application.php` manually

```
public function bootstrap(): void
{
    parent::bootstrap();
    
    // Other plugins
    $this->addPlugin('LordSimal/CakephpDbml');
}
```


## How to use

After installing the plugin you now have a new command available to you:

```
bin/cake generate_dbml 
```

After executing that you should have a new file in your applications root folder called `dbml-export.dbml`

This text file can then be copy-pasted into e.g. https://dbdiagram.io where you can very easily drag&drop your tables into a structure which is more visually appealing

### Possible warning messages

It is possible that you get warning messages like these:

```
-> % bin/cake generate_dbml   
Table for Tokens (tokens) from Plugin "Tools" is not present
```

This means that you current database schema doesn't have that table but your code (or in this case the plugin "Tools") contains a table file which has been detected.


## Configuration

All default config-keys can be seen in this plugins `config/config.php` file

* `Dbml.path` => The path where the generated file will be placed
* `Dbml.filename` => The filename
* `Dbml.blacklistedPlugins`=> Array of plugins which should be skipped
* `Dbml.blacklistedTables` => Array of table names which should be skipped

You can **extend** any of these values just by adding the following to your `config/app_local.php`

```
return [
    'Dbml' => [
        'blacklistedPlugins' => [
            'CakeDC/Users',
        ],
        'blacklistedTables' => [
            'social_accounts',
            'token',
            'queued_jobs',
            'queue_processes'
        ]
    ],
];
```


## How does it work?

Executing the command from above will look through all your **table files** (in your app as well as all installed plugins) and load their schema data including relations.

By default it does exclude the plugins `DebugKit` as well as `Migrations` which are present in new CakePHP applications

If you want to have more information on which tables and which associations are being detected execute the command like so

```
bin/cake generate_dbml -v
```

After gathering all the table and association data they get formatted into a structure which is more easily written to the DBML file.


## Acknowledgement

Big shoutout to @dereuromark and especially his https://github.com/dereuromark/cakephp-model-graph plugin which definitely inspired and helped me create this plugin!