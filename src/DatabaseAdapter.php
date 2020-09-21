<?php
/**
 * MysqlImport.
 *
 * Import database from command-line.
 *
 * @category   Command-line
 *
 * @author     Francesco Bianco
 * @copyright  2015-2019 Javanile
 */

namespace Javanile\MysqlImport;

class DatabaseAdapter
{
    /**
     * @var
     */
    protected $link;

    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var string|null
     */
    protected $port;

    /**
     * @var string|null
     */
    protected $database;

    /**
     * @var string|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var string|null
     */
    protected $rootPassword;

    /**
     * @var string|null
     */
    protected $exists;

    /**
     * @var string|null
     */
    protected $empty;

    /**
     * @var string
     */
    protected $connectError;

    /**
     * Connect to database.
     *
     * @param $user
     * @param $password
     *
     * @return mysqli
     */
    protected function connect($user, $password)
    {
        try {
            $this->connectError = null;
            $this->link = @mysqli_connect($this->host, $user, $password, '', $this->port);
            if (!$this->link) {
                $this->connectError = mysqli_connect_errno();
            }
        } catch (\Throwable $e) {
            $this->logs[] = '['.date('Y-m-d H:i:s').'] ERROR - Message: '.$e->getMessage()."\n".$e->getTraceAsString();
        }

        return $this->link;
    }

    /**
     * Check if database exists.
     *
     * @return array|null
     */
    protected function exists()
    {
        $this->exists = @mysqli_fetch_assoc(@mysqli_query($this->link, "SHOW DATABASES LIKE '{$this->database}'"));

        return $this->exists;
    }

    /**
     * Check if database is blank.
     *
     * @return bool
     */
    protected function blank()
    {
        mysqli_select_db($this->link, $this->database);

        $tables = @mysqli_fetch_all(@mysqli_query($this->link, 'SHOW TABLES'));

        $this->empty = count($tables) == 0;

        return $this->empty;
    }

    /**
     * Create new database.
     *
     * @return bool|mysqli_result
     */
    protected function create()
    {
        $create = mysqli_query(
            $this->link,
            "CREATE DATABASE `{$this->database}` CHARACTER SET utf8 COLLATE utf8_general_ci"
        );

        return $create;
    }

    /**
     * Drop the database.
     *
     * @param null|mixed $agree
     *
     * @return bool|mysqli_result
     */
    public function drop($agree = null)
    {
        if ($agree != 'yes') {
            return;
        }

        $sql = "DROP DATABASE `{$this->database}`";

        return mysqli_query($this->link, $sql);
    }

    /**
     * Get database information.
     */
    public function getInfo()
    {
        return [
            'host'     => $this->host,
            'port'     => $this->port,
            'database' => $this->database,
        ];
    }
}
