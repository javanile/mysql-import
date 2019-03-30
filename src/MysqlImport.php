<?php
/**
 * MysqlImport.
 *
 * Import database from command-line.
 *
 * @category   Command-line
 *
 * @author     Francesco Bianco
 * @copyright  2018 Javanile
 */

namespace Javanile\MysqlImport;

class MysqlImport
{
    /**
     * @var string
     */
    protected $file;

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
    protected $state;

    /**
     * @var int
     */
    protected $exitCode;

    /**
     * MysqlImport constructor.
     *
     * @param $env
     * @param $argv
     */
    public function __construct($env, $argv)
    {
        $this->exitCode = 0;
        $this->state = 'ready';

        $defaultDatabase = isset($env['WORDPRESS_DB_PASSWORD']) ? 'wordpress' : 'database';

        $opts = [
            ['host', 'mysql', '-h', 'MYSQL_HOST', 'DB_HOST', 'WORDPRESS_DB_HOST'],
            ['port', '3306', '-P', 'MYSQL_PORT', 'DB_PORT'],
            ['database', $defaultDatabase, '-d', 'MYSQL_DATABASE', 'DB_NAME', 'WORDPRESS_DB_NAME'],
            ['user', null, '-u', 'MYSQL_USER', 'DB_USER', 'WORDPRESS_DB_USER'],
            ['password', null, '-p', 'MYSQL_PASSWORD', 'DB_PASSWORD', 'WORDPRESS_DB_PASSWORD'],
            ['rootPassword', null, null, 'MYSQL_ROOT_PASSWORD', 'DB_ROOT_PASSWORD'],
        ];

        foreach ($opts as $opt) {
            // Get default value
            $value = $opt[1];

            // Get value from environment
            for ($index = 3; $index < count($opt); $index++) {
                $value = isset($env[$opt[$index]]) && $env[$opt[$index]] ? $env[$opt[$index]] : $value;
            }

            // Get value from command-line argument
            if ($opt[2] && $arg = preg_grep('/^'.$opt[2].'[\S]*/', $argv)) {
                $value = substr(end($arg), strlen($opt[2]));
            }

            // Place value on property
            $this->{$opt[0]} = $value;
        }

        // Set rootPassword using password as default
        if (is_null($this->rootPassword) && !is_null($this->password)) {
            $this->rootPassword = $this->password;
        }

        // Set fix host port
        if (preg_match('/:([0-9]+)$/', $this->host, $matches)) {
            $this->host = substr($this->host, 0 , -1 - strlen($matches[1]));
            $this->port = $matches[1];
        }

        // Look file to import
        foreach ($argv as $arg) {
            if ($arg[0] == '-') {
                continue;
            }
            $this->file = $arg;
        }
    }

    /**
     * Command entrypoint.
     */
    public function run()
    {
        if (!$this->file) {
            return $this->message('required sql file to import.');
        }

        if (!file_exists($this->file)) {
            return $this->message("sql file '{$this->file}' not found.");
        }

        if ($message = $this->tryUserAndPassword()) {
            return $message;
        }

        return $this->tryRootPassword();
    }

    /**
     * Try to import using standard user and password.
     *
     * @return string|false
     */
    protected function tryUserAndPassword()
    {
        // process standard mysql user
        if (!$this->user || !$this->password) {
            return false;
        }

        // first attempt avoid database delay
        if (!$this->connect($this->user, $this->password)) {
            sleep(5);
        }

        // second attempt real check
        if (!$this->connect($this->user, $this->password)) {
            return false;
        }

        //
        if (!$this->exists()) {
            $this->create();
        }

        //
        if (!$this->blank()) {
            return $this->messageDatabaseNotBlank();
        }

        return $this->import();
    }

    /**
     * Try to connect and import using root user and password.
     *
     * @return bool|mysqli_result|mixed
     */
    protected function tryRootPassword()
    {
        // process root mysql use
        if (!$this->rootPassword) {
            return $this->message('required at least root password.');
        }

        // first attempt avoid database delay
        if (!$this->connect('root', $this->rootPassword)) {
            sleep(5);
        }

        // second attempt real check
        if (!$this->connect('root', $this->rootPassword)) {
            return $this->messageConnectionProblem('root');
        }

        // try to import
        if ($this->exists()) {
            if ($this->blank()) {
                return $this->import();
            }

            return $this->messageDatabaseNotBlank();
        } elseif ($this->create()) {
            return $this->import();
        }

        return $this->message(mysqli_error($this->link));
    }

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
            $this->link = mysqli_connect($this->host, $user, $password, '', $this->port);
        } catch (\Throwable $e) {
            $log = 'ERROR: '.$e->getMessage()."\n".$e->getTraceAsString()."\n";
            file_put_contents('mysql-import.log', $log, FILE_APPEND);
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

        $drop = mysqli_query($this->link, $sql);

        return $drop;
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
     * @return bool|mysqli_result
     */
    public function import()
    {
        mysqli_select_db($this->link, $this->database);

        $sql = '';
        foreach (file($this->file) as $line) {
            if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
            }
            $sql .= $line;
            if (substr(trim($line), -1, 1) == ';') {
                if (!mysqli_query($this->link, $sql)) {
                    return $this->message(mysqli_error($this->link));
                }
                $sql = '';
            }
        }

        return $this->message("database named '{$this->database}' successfully imported.");
    }

    /**
     * @param $message
     *
     * @return mixed
     */
    protected function message($message)
    {
        return '[mysql-import] '.$message;
    }

    /**
     * Message for not blank database.
     *
     * @return string
     */
    protected function messageDatabaseNotBlank()
    {
        $this->exitCode = 0;

        return $this->message("required blank database for import.");
    }

    /**
     * Message for connection problem.
     *
     * @param $user
     *
     * @return string
     */
    protected function messageConnectionProblem($user)
    {
        $this->exitCode = 2;

        return $this->message(
            "connection problem for user '{$user}' on host '{$this->host}' with error: ".mysqli_connect_error()
        );
    }

    /**
     * Get exit code after run.
     *
     * @return integer
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * Get information.
     */
    public function getInfo()
    {
        return [
            'state' => $this->state,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database
        ];
    }
}
