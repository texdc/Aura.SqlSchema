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

/**
 *
 * An interface for migration locator implementations.
 *
 * @package Aura.SqlSchema
 *
 */
interface LocatorInterface
{
    /**
     *
     * Returns a version's migration.
     *
     * @param int $version The version number.
     *
     * @throws \Aura\SqlSchema\Exception On an invalid/unknown version number.
     *
     * @return MigrationInterface
     *
     */
    public function get($version);

    /**
     *
     * Returns the most recent version number.
     *
     * @return int
     *
     */
    public function latestVersion();
}
