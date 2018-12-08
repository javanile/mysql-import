<?php
/**
 * foreground-check.php.
 *
 * Check database before start container
 *
 * @category   CategoryName
 *
 * @author     Francesco Bianco
 * @copyright  2018 Javanile.org
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
     * MysqlImport constructor.
     *
     * @param $env
     * @param $argv
     */
    public function __construct($env, $argv)
    {
        $opts = [
            ['host', 'mysql', 'MYSQL_HOST', '-h'],
            ['database', 'database', 'MYSQL_DATABASE', '-d'],
            ['user', null, 'MYSQL_USER', '-u'],
            ['password', null, 'MYSQL_PASSWORD', '-p'],
            ['rootPassword', null, 'MYSQL_ROOT_PASSWORD', null],
        ];

        foreach ($opts as $opt) {
            $value = $opt[1];
            if (isset($env[$opt[2]])) {
                $value = $env[$opt[2]];
            }

            if ($opt[3] && $arg = preg_grep('/^'.$opt[3].'[\S]*/', $argv)) {
                $value = substr(end($arg), strlen($opt[3]));
            }

            $this->{$opt[0]} = $value;
        }

        foreach ($argv as $arg) {
            if ($arg[0] == '-') {
                continue;
            }
            $this->file = $arg;
        }
    }

    /**
     *
     */
    public function run()
    {
        if (!$this->file) {
            return $this->message('Missing file to import');
        }

        if ($message = $this->tryUserAndPassword()) {
            return $message;
        }

        // process root mysql use
        if (!$this->rootPassword) {
            return $this->message('MYSQL_ROOT_PASSWORD_MISSING');
        }

        // first attempt avoid database delay
        if (!$this->connect('root', $this->rootPassword)) {
            sleep(5);
        }

        // second attempt real check
        if (!$this->connect('root', $this->rootPassword)) {
            return $this->message(':'.mysqli_connect_error());
        }

        if ($this->exists()) {
            if ($this->blank()) {
                return $this->import();
            }

            return $this->message('not black database');
        } elseif ($this->create()) {
            return $this->import();
        }

        return $this->message('MYSQL_QUERY_ERROR_'.mysqli_errno($this->link));
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
            return false;
        }

        //
        if (!$this->blank()) {
            return $this->message('not blank database');
        }

        return $this->import();
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
        $this->link = mysqli_connect($this->host, $user, $password);

        return $this->link;
    }

    /**
     * @return array|null
     */
    protected function exists()
    {
        $this->exists = @mysqli_fetch_assoc(@mysqli_query($this->link, "SHOW DATABASES LIKE '{$this->name}'"));

        return $this->exists;
    }

    /**
     * @return bool
     */
    protected function blank()
    {
        $this->empty = !@mysqli_fetch_assoc(@mysqli_query($this->link, 'SHOW TABLES'));

        return $this->empty;
    }

    /**
     * @return bool|mysqli_result
     */
    protected function create()
    {
        $create = mysqli_query($this->link, "CREATE DATABASE {$this->database} CHARACTER SET utf8 COLLATE utf8_general_ci");

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

        return $this->message('database successfully imported.');
    }

    /**
     * @param $message
     *
     * @return mixed
     */
    protected function message($message)
    {
        //**
        return 'mysql-import: '.$message;
    }
}
