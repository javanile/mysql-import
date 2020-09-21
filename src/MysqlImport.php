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

class MysqlImport extends DatabaseAdapter
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $lockFile;

    /**
     * @var int
     */
    protected $exitCode;

    /**
     * @var bool
     */
    protected $doWhile;

    /**
     * @var bool
     */
    protected $force;

    /**
     * @var string
     */
    protected $unknownOption;

    /**
     * @var Loader
     */
    protected $loader;

    /**
     * MysqlImport constructor.
     *
     * @param $env
     * @param $argv
     */
    public function __construct($env, $argv)
    {
        $this->exitCode = 0;
        $this->loader = new Loader();

        if (in_array('--do-while', $argv)) {
            $argv = array_diff($argv, ['--do-while']);
            $this->doWhile = true;
        }

        if (in_array('--force', $argv)) {
            $argv = array_diff($argv, ['--force']);
            $this->force = true;
        }

        $defaultDatabase = isset($env['WORDPRESS_DB_PASSWORD']) ? 'wordpress' : 'database';

        $opts = [
            ['host', 'mysql', '-h', 'MYSQL_HOST', 'DB_HOST', 'WORDPRESS_DB_HOST'],
            ['port', '3306', '-P', 'MYSQL_PORT', 'DB_PORT'],
            ['database', $defaultDatabase, '-d', 'MYSQL_DATABASE', 'DB_NAME', 'WORDPRESS_DB_NAME'],
            ['user', null, '-u', 'MYSQL_USER', 'DB_USER', 'WORDPRESS_DB_USER'],
            ['password', null, '-p', 'MYSQL_PASSWORD', 'DB_PASSWORD', 'WORDPRESS_DB_PASSWORD'],
            ['rootPassword', null, null, 'MYSQL_ROOT_PASSWORD', 'DB_ROOT_PASSWORD'],
        ];

        $properties = [];
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
                $argv = array_diff($argv, $arg);
            }

            // Place value on property
            $properties[$opt[0]] = $value;
        }

        // Initialize base class
        parent::__construct($properties);

        // Get file to import or work without file
        if (in_array('--no-file', $argv)) {
            $this->file = false;
            $this->lockFile = false;
            $argv = array_diff($argv, ['--no-file']);
        } else {
            foreach ($argv as $arg) {
                if ($arg[0] != '-') {
                    $this->file = trim($arg);
                    $this->lockFile = $this->file.'.lock';
                    $argv = array_diff($argv, [$arg]);
                    break;
                }
            }
        }

        // Set first unprocessed argument as unknown option
        $this->unknownOption = reset($argv);
    }

    /**
     * Command entry-point.
     */
    public function run()
    {
        if ($this->unknownOption) {
            return $this->messageUnknownOption();
        }

        if ($this->doWhile) {
            $time = time() + 300;
            do {
                $this->connect('root', $this->rootPassword);
                $this->loader->waiting(5, 'Waiting for database server...');
            } while ($time > time() && $this->error && $this->error >= 2000);
        }

        if (!$this->file && $this->file !== false) {
            return $this->message('required sql file to import.');
        }

        if (!file_exists($this->file) && $this->file !== false) {
            return $this->message("sql file '{$this->file}' not found.");
        }

        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
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
            $this->loader->waiting(10, 'Connecting to database with provided user...');
        }

        // second attempt real check
        if (!$this->connect($this->user, $this->password)) {
            return false;
        }

        // Create database if not exists
        if (!$this->exists()) {
            $this->create();
        }

        // Exit if database in not empty
        if (!$this->blank() && !$this->force) {
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
            $this->loader->waiting(10, 'Connecting to database as root user...');
        }

        // second attempt real check
        if (!$this->connect('root', $this->rootPassword)) {
            return $this->messageConnectionProblem('root');
        }

        // try to import
        if ($this->exists()) {
            if ($this->blank() || $this->force) {
                return $this->import();
            }

            return $this->messageDatabaseNotBlank();
        } elseif ($this->create()) {
            return $this->import();
        }

        return $this->message(mysqli_error($this->link));
    }

    /**
     * @return bool|mysqli_result
     */
    public function import()
    {
        if ($this->file === false) {
            return $this->message('blank database is ready.');
        }

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

        if ($sql) {
            if (!mysqli_query($this->link, $sql)) {
                return $this->message(mysqli_error($this->link));
            }
        }

        $this->writeLockFile();

        return $this->message("database named '{$this->database}' successfully imported.");
    }

    /**
     * @param $message
     *
     * @return mixed
     */
    protected function message($message)
    {
        return ucfirst($message);
    }

    /**
     * Message for not blank database.
     *
     * @return string
     */
    protected function messageDatabaseNotBlank()
    {
        $this->exitCode = 0;

        return $this->message('required blank database for import.');
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
        $this->exitCode = 1;

        $message = mysqli_connect_error();
        $errorNumber = mysqli_connect_errno();

        return $this->message(
            "connection problem for '{$user}' on '{$this->host}' with error: {$message} ({$errorNumber})."
        );
    }

    /**
     * Message for connection problem.
     *
     * @param $user
     *
     * @return string
     */
    protected function messageUnknownOption()
    {
        $this->exitCode = 2;

        return $this->message(
            "Unknown option '{$this->unknownOption}'."
        );
    }

    /**
     *
     */
    protected function writeLockFile()
    {
        $json = [
            'database' => $this->database
        ];

        file_put_contents($this->lockFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get exit code after run.
     *
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }
}
