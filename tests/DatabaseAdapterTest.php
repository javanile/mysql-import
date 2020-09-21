<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\MysqlImport\DatabaseAdapter;
use PHPUnit\Framework\TestCase;

class DatabaseAdapterTest extends TestCase
{
    public function testDatabaseAdapter()
    {
        $properties = [
            'host' => 'localhost',
            'port' => '1080',
            'database' => 'db_0'
        ];

        $databaseAdapter = new DatabaseAdapter($properties);

        $this->assertEquals($databaseAdapter->getInfo(), $properties);
    }
}
