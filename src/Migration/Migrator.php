<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.SqlSchema
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlSchema\Migration;

use Aura\SqlSchema\Exception;
use PDO;

/**
 *
 * Migrates schema versions.
 *
 * @package Aura.SqlSchema
 *
 */
class Migrator
{
    /**
     *
     * A Pdo connection.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     *
     * A MigrationLocator for loading migration versions.
     *
     * @var MigrationLocator
     *
     */
    protected $migration_locator;

    /**
     *
     * A callable for rendering messages.
     *
     * @var callable
     *
     */
    protected $output_callable;

    /**
     *
     * The table name for storing migrations.
     *
     * @var string
     *
     */
    protected $table;

    /**
     *
     * The column name to hold the migration version.
     *
     * @var string
     *
     */
    protected $col;

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A database connection.
     *
     * @param MigrationLocator $migration_locator A migration version locator.
     *
     * @param callable $output_callable A message handling callable.
     *
     * @param string $table A database table name.
     *
     * @param string $col A database column name.
     *
     */
    public function __construct(
        PDO $pdo,
        MigrationLocator $migration_locator,
        $output_callable,
        $table = 'schema_migration',
        $col = 'version'
    ) {
        $this->setPdo($pdo);

        $this->migration_locator = $migration_locator;
        $this->output_callable = $output_callable;
        $this->table = $table;
        $this->col = $col;
    }

    public function migrate($to = null)
    {
        $from = $this->fetchVersion();
        if ($to === null) {
            $to = $this->migration_locator->count();
        }
        if ($from == $to) {
            $message = "Already at version {$to}, skipping migration.";
            $this->output($message);
            return 1;
        }

        $direction = ($to > $from) ? 'up' : 'down';
        return $this->applyMigrations($direction, $from, $to);
    }

    /**
     *
     * Performs an 'up' migration.
     *
     * @param int|null $to The target version number; increments the current version
     * by one when empty.
     *
     * @return int 1 on success, 0 on failure.
     *
     */
    public function up($to = null)
    {
        $from = $this->fetchVersion();
        $to = $this->toVersion($to, $from, +1);
        if ($from >= $to) {
            $message = "Currently at version {$from}, so cannot migrate up to version {$to}.";
            $this->output($message);
            return 1;
        }
        return $this->applyMigrations('up', $from, $to);
    }

    /**
     *
     * Performs a 'down' migration.
     *
     * @param int|null $to The target version number; decrements the current version
     * by one when empty.
     *
     * @return int 1 on success, 0 on failure.
     *
     */
    public function down($to = null)
    {
        $from = $this->fetchVersion();
        $to = $this->toVersion($to, $from, -1);
        if ($from <= $to) {
            $message = "Cannot migrate down to version {$to} "
                . "when already at or below it ({$from}).";
            $this->output($message);
            return 1;
        }
        return $this->applyMigrations('down', $from, $to);
    }

    protected function applyMigrations($direction, $from, $to)
    {
        $this->beginTransaction();
        $this->output("Migrating {$direction} from {$from} to {$to}.");
        $method = 'applyMigrations' . ucfirst($direction);
        try {
            $this->$method($from, $to);
            $this->updateVersion($to);
            $this->commit();
            $this->output("Migration {$direction} from {$from} to {$to} committed!");
            return 0;
        } catch (Exception $e) {
            $this->rollBack();
            $this->output("Migration {$direction} from {$from} to {$to} failed.");
            $this->output($e->getMessage());
            $this->output("Rolled back to version {$from}.");
            return 1;
        }
    }

    protected function applyMigrationsUp($from, $to)
    {
        for ($version = $from + 1; $version <= $to; $version += 1) {
            $this->applyMigration('up', 'to', $version);
        }
    }

    protected function applyMigrationsDown($from, $to)
    {
        for ($version = $from; $version > $to; $version -= 1) {
            $this->applyMigration('down', 'from', $version);
        }
    }

    protected function applyMigration(
        $direction,
        $to_from,
        $version
    ) {
        $migration = $this->getMigration($version);
        call_user_func(array($migration, $direction));
        $this->output("Migrated {$direction} {$to_from} {$version}.");
    }

    protected function getMigration($version)
    {
        $migration = $this->migration_locator->get($version);
        if (! $migration instanceof MigrationInterface) {
            $message = get_class($migration) . ' does not implement '
                     . '\\Aura\\SqlSchema\\MigrationInterface.';
            throw new Exception($message);
        }
        return $migration;
    }

    protected function output($str)
    {
        call_user_func($this->output_callable, $str);
    }

    protected function toVersion($to, $from, $by)
    {
        if ($to === null) {
            $to = $from + $by;
        }
        return (int) $to;
    }

    protected function fetchVersion()
    {
        $stm = "SELECT {$this->col} FROM {$this->table}";
        $sth = $this->pdo->prepare($stm);
        $sth->execute();
        return (int) $sth->fetchColumn(0);
    }

    protected function updateVersion($version)
    {
        $stm = "UPDATE {$this->table} SET {$this->col} = {$version}";
        $sth = $this->pdo->prepare($stm);
        $sth->execute();
    }

    protected function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    protected function commit()
    {
        $this->pdo->commit();
    }

    protected function rollBack()
    {
        $this->pdo->rollBack();
    }

    /**
     *
     * Sets the database connection.
     *
     * @param PDO $pdo A database connection.
     *
     * @throws Exception When the connection does not use ERRMODE_EXCEPTION.
     *
     */
    protected function setPdo(PDO $pdo)
    {
        if (PDO::ERRMODE_EXCEPTION != $pdo->getAttribute(PDO::ATTR_ERRMODE)) {
            throw new Exception('PDO must use ERRMODE_EXCEPTION for migrations.');
        }
        $this->pdo = $pdo;
    }
}
