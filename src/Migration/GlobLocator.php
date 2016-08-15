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
 * Locates migrations with a `glob` pattern.
 *
 * @package Aura.SqlSchema
 *
 */
class GlobLocator implements LocatorInterface
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
     * A list of version files.
     *
     * @var string[]
     *
     */
    protected $versions;

    /**
     *
     * A namespace for the migrations.
     *
     * @var string
     *
     */
    protected $namespace;

    /**
     *
     * A cache of migration instances.
     *
     * @var MigrationInterface[]
     *
     */
    protected $instances;

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A database connection.
     *
     * @param string $directory A directory containing migration classes.
     *
     * @param string $namespace A namespace for the migrations.
     *
     * @throws Exception When the directory is invalid.
     *
     */
    public function __construct(PDO $pdo, $directory, $namespace)
    {
        $directory = rtrim(realpath($directory), '/');
        if (!is_dir($directory)) {
            throw new Exception("Invalid migration directory [$directory].");
        }

        $this->pdo       = $pdo;
        $this->versions  = glob($directory . '/V*.php');
        $this->namespace = $namespace;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Aura\SqlSchema\Migration\LocatorInterface::get()
     *
     */
    public function get($version)
    {
        if (!isset($this->instances[$version])) {
            $this->instances[$version] = $this->getVersion($version);
        }

        return $this->instances[$version];
    }

    /**
     *
     * {@inheritDoc}
     * @see \Aura\SqlSchema\Migration\LocatorInterface::latestVersion()
     *
     */
    public function latestVersion()
    {
        $fileName = $this->versions[count($this->versions) - 1];
        return (int) str_replace(array('V', '.php'), '', basename($fileName));
    }

    /**
     *
     * Instantiates a migration version.
     *
     * @param int $version The version to instantiate.
     *
     * @throws Exception When the version is invalid.
     *
     * @return MigrationInterface
     *
     */
    protected function getVersion($version)
    {
        if (!isset($this->versions[$version - 1])) {
            throw new Exception("Migration $version not found.");
        }
        $fileName = $this->versions[$version - 1];
        require_once $fileName;
        $className = sprintf('%s\\%s', $this->namespace, basename($fileName, '.php'));

        return new $className($this->pdo);
    }
}
