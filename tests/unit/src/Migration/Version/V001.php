<?php
namespace Aura\SqlSchema\Migration\Version;

use Aura\SqlSchema\Migration\AbstractMigration;

class V001 extends AbstractMigration
{
    public function up()
    {
        $this->pdo->exec("CREATE TABLE v1table (name VARCHAR(50))");
    }

    public function down()
    {
        $this->pdo->exec("DROP TABLE v1table");
    }
}
