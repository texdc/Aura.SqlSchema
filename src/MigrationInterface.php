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

/**
 *
 * An interface for migration implementations.
 *
 * @package Aura.SqlSchema
 *
 */
interface MigrationInterface
{
    /**
     *
     * Executes an 'up' migration.
     *
     * @return void
     *
     */
    public function up();

    /**
     *
     * Executes a 'down' migration.
     *
     * @return void
     *
     */
    public function down();
}
