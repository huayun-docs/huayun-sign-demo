<?php
/**
 * 自动加载
 * @author Tu
 */

class Autoloader
{
    private $directory;
    private $prefix;
    private $prefixLength;

    public function __construct($baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;
        $this->prefix = '';
        $this->prefixLength = strlen($this->prefix);
    }

    public static function register($prepend = false)
    {
        spl_autoload_register([new self(), 'autoload'], true, $prepend);
    }

    public function autoload($className)
    {
        $parts = explode('\\', substr($className, $this->prefixLength));
        $filepath = $this->directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

        if (is_file($filepath)) {
            require $filepath;
        }
    }
}
