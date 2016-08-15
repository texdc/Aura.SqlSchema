<?php

namespace Aura\SqlSchema\Migration;

use PDO;

class GlobLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GlobLocator
     */
    private $locator;

    /**
     * @var PDO
     */
    private static $pdo;

    public static function setUpBeforeClass()
    {
        static::$pdo = new PDO('sqlite::memory:');
        static::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function setUp()
    {
        $this->locator = new GlobLocator(
            static::$pdo,
            __DIR__ . '/Version/',
            'Aura\SqlSchema\Migration\Version'
        );
    }

    public function testConstructorValidatesDirectory()
    {
        $this->expectException('Aura\SqlSchema\Exception');
        $locator = new GlobLocator(static::$pdo, 'foo', 'bar');
    }

    public function testGetValidatesVersion()
    {
        $this->expectException('Aura\SqlSchema\Exception');
        $this->locator->get(5);
    }

    public function testGetReturnsMigration()
    {
        $version = $this->locator->get(1);
        $this->assertInstanceOf('Aura\SqlSchema\Migration\MigrationInterface', $version);
    }

    public function testLatestVersionReturnsInt()
    {
        $version = $this->locator->latestVersion();
        $this->assertInternalType('int', $version);
    }
}
