<?php

namespace Serebro\MongoMigration;

class Console
{

    /**
     * @param boolean $raw If set to true, returns the raw string without trimming
     * @return string the string read from stdin
     */
    public static function stdIn($raw = false)
    {
        return $raw ? fgets(\STDIN) : rtrim(fgets(\STDIN), PHP_EOL);
    }

    /**
     * @param string $string the string to print
     * @return int|boolean Number of bytes printed or false on error
     */
    public static function stdOut($string)
    {
        return fwrite(\STDOUT, $string);
    }

    /**
     * @param string $string the string to print
     * @return int|boolean Number of bytes printed or false on error
     */
    public static function stdErr($string)
    {
        return fwrite(\STDERR, $string);
    }

    /**
     * @param string $prompt the prompt to display before waiting for input (optional)
     * @return string the user's input
     */
    public static function input($prompt = null)
    {
        if (isset($prompt)) {
            static::stdout($prompt);
        }

        return static::stdin();
    }

    /**
     * @param string $string the text to print
     * @return integer|boolean number of bytes printed or false on error.
     */
    public static function output($string = null)
    {
        return static::stdout($string . PHP_EOL);
    }

    /**
     * @param string $string the text to print
     * @return integer|boolean number of bytes printed or false on error.
     */
    public static function error($string = null)
    {
        return static::stdErr($string . PHP_EOL);
    }

    /**
     * Asks user to confirm by typing y or n.
     *
     * @param string $message to echo out before waiting for user input
     * @param boolean $default this value is returned if no selection is made.
     * @return boolean whether user confirmed
     */
    public static function confirm($message, $default = true)
    {
        echo $message . ' (yes|no) [' . ($default ? 'yes' : 'no') . ']:';
        $input = trim(static::stdin());

        return empty($input) ? $default : !strncasecmp($input, 'y', 1);
    }

}
