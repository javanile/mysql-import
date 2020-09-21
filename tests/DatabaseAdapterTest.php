<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class DatabaseAdapterTest extends TestCase
{
    public function testDatabaseAdapter()
    {

        $this->assertEquals(2, $mysqlImport->getExitCode());
    }
}
