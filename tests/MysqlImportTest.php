<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testImportByDefault()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');

        $app = new MysqlImport([], ['-proot', $sqlFile]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testImportWithUserAndPassword()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');

        $app = new MysqlImport([], ['-uroot', '-proot', $sqlFile]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testDropAndCreateDatabase()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'root'], [$sqlFile]);

        $app->run();
        $app->drop();
        $app->drop('yes');

        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testNotBlankDatabase()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = '[mysql-import] required blank database for import.';

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'root'], [$sqlFile]);
        $app->run();

        $app = new MysqlImport([], ['-proot', $sqlFile]);
        $this->assertEquals($message, $app->run());

        $app = new MysqlImport(['DB_USER' => 'root', 'DB_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertEquals($message, $app->run());

        $app->drop('yes');
    }

    public function testConnectionProblemWrongPassword()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] connection problem for user 'root' on host 'mysql' with error: ";

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'wrong'], [$sqlFile]);
        $this->assertStringStartsWith($message, $app->run());
    }

    public function testConnectionProblemWrongHost()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] connection problem for user 'root' on host 'wrong' with error: ";

        $app = new MysqlImport(['MYSQL_HOST' => 'wrong', 'MYSQL_ROOT_PASSWORD' => 'wrong'], [$sqlFile]);
        $this->assertStringStartsWith($message, $app->run());

        $app = new MysqlImport([
            'MYSQL_HOST'     => 'wrong',
            'MYSQL_USER'     => 'root',
            'MYSQL_PASSWORD' => 'wrong', ],
            [$sqlFile]
        );
        $this->assertStringStartsWith($message, $app->run());
        $this->assertEquals(2, $app->getExitCode());
    }

    public function testMissingSqlFile()
    {
        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'root'], []);
        $this->assertEquals('[mysql-import] required sql file to import.', $app->run());

        $sqlFile = __DIR__.'/fixtures/not_exists.sql';
        $app = new MysqlImport([], ['-proot', $sqlFile]);
        $this->assertEquals("[mysql-import] sql file '{$sqlFile}' not found.", $app->run());
    }

    public function testMissingRootPassword()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';

        $app = new MysqlImport([], [$sqlFile]);
        $this->assertEquals('[mysql-import] required at least root password.', $app->run());
    }

    public function testHostPortFix()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';

        $app = new MysqlImport(['WORDPRESS_DB_HOST' => 'db:10101'], [$sqlFile]);
        $this->assertEquals([
            'state'    => 'ready',
            'host'     => 'db',
            'port'     => 10101,
            'database' => 'database',
        ], $app->getInfo());
    }

    public function testDefaultDatabaseName()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';

        $app = new MysqlImport(['WORDPRESS_DB_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertEquals([
            'state'    => 'ready',
            'host'     => 'mysql',
            'port'     => 3306,
            'database' => 'wordpress',
        ], $app->getInfo());
    }

    public function testSyntaxError()
    {
        $sqlFile = __DIR__.'/fixtures/syntax_error.sql';
        $message = '[mysql-import] You have an error in your SQL syntax; check the manual that corresponds to';

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertStringStartsWith($message, $app->run());
    }
}
