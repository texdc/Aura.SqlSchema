# Aura.SqlSchema

Provides facilities to read table names and table columns from a database
using a [PDO](http://php.net/PDO) connection.

## Foreword

### Installation

This library requires PHP 5.3 or later (PHP 7 is supported), and has no userland dependencies.

It is installable and autoloadable via Composer as [aura/sqlschema](https://packagist.org/packages/aura/sqlschema).

Alternatively, [download a release](https://github.com/auraphp/Aura.SqlSchema/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/auraphp/Aura.SqlSchema/badges/quality-score.png?b=develop-2)](https://scrutinizer-ci.com/g/auraphp/Aura.SqlSchema/)
[![Code Coverage](https://scrutinizer-ci.com/g/auraphp/Aura.SqlSchema/badges/coverage.png?b=develop-2)](https://scrutinizer-ci.com/g/auraphp/Aura.SqlSchema/)
[![Build Status](https://travis-ci.org/auraphp/Aura.SqlSchema.png?branch=develop-2)](https://travis-ci.org/auraphp/Aura.SqlSchema)

To run the unit tests at the command line, issue `phpunit -c tests/unit/`. (This requires [PHPUnit][] to be available as `phpunit`.)

[PHPUnit]: http://phpunit.de/manual/

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.


## Getting Started

### Instantiation

Instantiate a driver-specific schema object with a matching
[PDO](http://php.net/PDO) instance:

```php
use Aura\SqlSchema\ColumnFactory;
use Aura\SqlSchema\MysqlSchema; // for MySQL
use Aura\SqlSchema\PgsqlSchema; // for PostgreSQL
use Aura\SqlSchema\SqliteSchema; // for Sqlite
use Aura\SqlSchema\SqlsrvSchema; // for Microsoft SQL Server
use PDO;

// a PDO connection
$pdo = new PDO(...);

// a column definition factory
$column_factory = new ColumnFactory();

// the schema discovery object
$schema = new MysqlSchema($pdo, $column_factory);
```

### Retrieving Schema Information

To get a list of tables in the database, issue `fetchTableList()`:

```php
$tables = $schema->fetchTableList();
foreach ($tables as $table) {
    echo $table . PHP_EOL;
}
```

To get information about the columns in a table, issue `fetchTableCols()`:

```php
$cols = $schema->fetchTableCols('table_name');
foreach ($cols as $name => $col) {
    echo "Column $name is of type "
       . $col->type
       . " with a size of "
       . $col->size
       . PHP_EOL;
}
```

Each column description is a `Column` object with the following properties:

- `name`: (string) The column name

- `type`: (string) The column data type.  Data types are as reported by the database.

- `size`: (int) The column size.

- `scale`: (int) The number of decimal places for the column, if any.

- `notnull`: (bool) Is the column marked as `NOT NULL`?

- `default`: (mixed) The default value for the column. Note that sometimes
  this will be `null` if the underlying database is going to set a timestamp
  automatically.

- `autoinc`: (bool) Is the column auto-incremented?

- `primary`: (bool) Is the column part of the primary key?

## Migrations

### Versions

Migration versions should extend `Aura\SqlSchema\AbstractMigration` which expects a
[PDO](http://php.net/PDO) instance as a constructor argument.

```php
<?php
namespace My\Project\Migration;

use Aura\SqlSchema\AbstractMigration;

class V001 extends AbstractMigration
{
    public function up()
    {
        $this->pdo->exec("CREATE TABLE test (name VARCHAR(50))");
    }

    public function down()
    {
        $this->pdo->exec("DROP TABLE test");
    }
}
```

### Tracking Versions

Migration versions will be tracked in a `schema_migration` table.

```php
// initialize the migration table (if necessary)
$pdo->exec('CREATE TABLE schema_migration (version INT)');
$pdo->exec('INSERT INTO schema_migration (version) VALUES (0)');
```

### Running Migrations

Migrations can be run with a simple script.  The [PDO](http://php.net/PDO) instance
must be configured to use `PDO::ERRMODE_EXCEPTION`.  A `Aura\SqlSchema\Migrator`
takes the `PDO` instance, an `Aura\SqlSchema\MigrationLocator` with a list of version
factories, and an output `callable`.

```bash
#!/usr/local/bin/php
<?php

namespace My\Project\Migration;

require 'path/to/Aura.SqlSchema/autoload.php';

use Aura\SqlSchema\Migrator;
use Aura\SqlSchema\MigrationLocator;
use PDO;

// a PDO connection
$pdo = new PDO(...);
// migrations rely on exceptions for error handling
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// build a list of versions
$factories = array(
    function () use ($pdo) { return new V001($pdo); },
    function () use ($pdo) { return new V002($pdo); },
    function () use ($pdo) { return new V003($pdo); },
);

$migration_locator = new MigrationLocator($factories);
$output_callable = function ($message) {
    print " - " . $message . PHP_EOL;
};

$migrator = new Migrator($pdo, $migration_locator, $output_callable);
print $migrator->up();
```
