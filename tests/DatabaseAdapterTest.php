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
            'username' => 'root',
            'password' => 'secret'
        ];

        $databaseAdapter = new DatabaseAdapter($properties);

        $this->assertEquals($databaseAdapter->getInfo(), $properties);
    }
}
