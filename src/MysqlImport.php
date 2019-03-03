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

        $opts = [
            ['host', 'mysql', 'MYSQL_HOST', 'DB_HOST', '-h'],
            ['port', '3306', 'MYSQL_PORT', 'DB_PORT', '-P'],
            ['database', 'database', 'MYSQL_DATABASE', 'DB_NAME', '-d'],
            ['user', null, 'MYSQL_USER', 'DB_USER', '-u'],
            ['password', null, 'MYSQL_PASSWORD', 'DB_PASSWORD', '-p'],
            ['rootPassword', null, 'MYSQL_ROOT_PASSWORD', 'DB_ROOT_PASSWORD', null],
        ];

        foreach ($opts as $opt) {
            // Get value from environment
            $value = isset($env[$opt[2]]) && $env[$opt[2]] ? $env[$opt[2]]
                : (isset($env[$opt[3]]) && $env[$opt[3]] ? $env[$opt[3]] : $opt[1]);

            // Get value from command-line argument
            if ($opt[4] && $arg = preg_grep('/^'.$opt[4].'[\S]*/', $argv)) {
                $value = substr(end($arg), strlen($opt[4]));
            }

            // Place value on property
            $this->{$opt[0]} = $value;
        }

        // Set rootPassword usign password as default
        if (is_null($this->rootPassword) && !is_null($this->password)) {
            $this->rootPassword = $this->password;
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

        if ($this->exists()) {
            if ($this->blank()) {
                return $this->import();
            }

            return $this->messageDatabaseNotBlank();
        } elseif ($this->create()) {
            return $this->import();
        }

        return $this->message('MYSQL_QUERY_ERROR_'.mysqli_errno($this->link));
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
     *
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }
}
