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

class Loader
{
    /**
     * @var string
     */
    protected $loader;

    /**
     * Loader constructor.
     */
    public function __construct()
    {
        $this->loader = '/\______';
    }

    /**
     * @param mixed $second
     * @param string $message
     *
     * @return string
     */
    public function waiting($second = 10, $message = 'Waiting...')
    {
        $freq = 5;
        for ($i = 0; $i < $second * $freq; $i++) {
            $text = '['.substr($this->loader, 0, -1).'] ' . $message;
            $this->print($text);
            usleep(1000000 / $freq);
            $this->loader = substr($this->loader, -1) . substr($this->loader, 0, -1);
            $cleaner = str_repeat("\010", strlen($text));
            $this->print($cleaner . str_repeat(' ', strlen($text)) . $cleaner);
        }
    }

    /**
     * @param $input
     *
     * @return mixed
     */
    protected function print($input)
    {
        if (defined('PHPUNIT_MYSQL_IMPORT') && PHPUNIT_MYSQL_IMPORT) {
            return;
        }

        echo $input;
    }
}
