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
 * Locates migration implementations by their version.
 *
 * @package Aura.SqlSchema
 *
 */
class MigrationLocator
{
    /**
     *
     * The list of migration factories.
     *
     * @var callable[]
     *
     */
    protected $factories = array();

    /**
     *
     * A cache of migration instances.
     *
     * @var MigrationInterface[]
     *
     */
    protected $instances = array();

    /**
     *
     * Constructor.
     *
     * @param callable[] $factories A list of migration factories.
     *
     */
    public function __construct(array $factories = array())
    {
        $this->factories = $factories;
    }

    /**
     *
     * Returns a version's migration.
     *
     * @param int $version The version number.
     *
     * @throws Exception On an invalid/unknown version number.
     *
     * @return MigrationInterface
     *
     */
    public function get($version)
    {
        $key = $version - 1;

        if (! isset($this->factories[$key])) {
            throw new Exception("Migration {$version} not found.");
        }

        if (! isset($this->instances[$key])) {
            $factory = $this->factories[$key];
            $this->instances[$key] = $factory();
        }

        return $this->instances[$key];
    }
}
