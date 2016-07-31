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
namespace Aura\SqlSchema;

use PDO;

/**
 *
 * A base migration.
 *
 * @package Aura.SqlSchema
 *
 */
abstract class AbstractMigration implements MigrationInterface
{
    /**
     *
     * The database connection.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A database connection.
     *
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
