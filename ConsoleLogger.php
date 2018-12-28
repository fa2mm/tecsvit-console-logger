<?php

namespace tecsvit;

/**
 * Class ConsoleLogger
 * Date: 2018-07-15
 * @version 1.2.1
 *
 * @static integer  $levelError
 * @static string   $filePath
 * @static boolean  $verboseK
 * @static boolean  $defaultFilePath
 *
 * @use \tecsvit\FileHelper
 */
class ConsoleLogger
{
    const SIMPLE                    = 0;
    const LOG                       = 1;
    const WARNING                   = 20;
    const ERROR                     = 30;

    public static $levelError       = 3;

    public static $filePath         = null;

    public static $verbose          = true;

    private static $defaultFilePath = '/logs/log.txt';

    /**
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {
        if (true === self::$verbose && php_sapi_name() === 'cli') {
            if (method_exists(__CLASS__, $method)) {
                return call_user_func_array([__CLASS__, $method], $arguments);
            } else {
                throw new \Exception('Method not found');
            }
        }
    }

    /**
     * @param mixed $data
     * @param int   $type
     * @param bool  $condition
     * @return void
     */
    private static function trace($data = '', $type = self::LOG, $condition = true)
    {
        if (true === $condition) {
            $trace = self::getTrace();

            self::log($data, false, $type, $trace);
        }
    }

    /**
     * @param string $data
     * @param bool   $condition
     * @param string $filePath
     * @return void
     */
    private static function file($data = '', $condition = true, $filePath = null)
    {
        if (true === $condition) {
            $trace = self::getTrace();

            self::logFile($data, $trace, '', $filePath);
        }
    }

    /**
     * @param mixed  $data
     * @param bool   $return
     * @param int    $type
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    private static function log($data, $return = false, $type = self::LOG, $prefix = '', $suffix = '')
    {
        if (true === $return) {
            return $data . PHP_EOL;
        } else {
            $result = self::decoratorAll($data, $type, $prefix);
            $result .= self::decorator($suffix, $type, true);
            echo $result;
        }
    }

    /**
     * @param mixed  $data
     * @param bool   $return
     * @param int    $type
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    private static function debugLog($data, $return = false, $type = self::LOG, $prefix = '', $suffix = '')
    {
        $result = self::decoratorAll($data, $type, $prefix);
        $result .= self::decorator($suffix, $type, true);

        if (true === $return) {
            return $result;
        } else {
            echo $result;
        }
    }

    /**
     * @param        $data
     * @param int    $type
     * @param string $prefix
     * @param string $suffix
     * @return void
     */
    private static function rlog($data, $type = self::LOG, $prefix = '', $suffix = '')
    {
        echo self::decorator("\r\r", $type);
        echo self::decoratorAll($data, $type, $prefix);
        echo self::decorator($suffix, $type, false);
    }

    /**
     * @param mixed     $data
     * @param int       $type
     * @param string    $prefix
     * @return string
     */
    private static function decoratorAll($data, $type, $prefix)
    {
        $result = self::decorator($prefix, $type);

        if (is_object($data) | is_array($data)) {
            $result .= self::decorator(var_export($data), $type);
        } else {
            $result .= self::decorator($data, $type);
        }

        return $result;
    }

    /**
     * @param string $content
     * @param string $type
     * @param bool   $last
     * @return string
     */
    private static function decorator($content, $type = null, $last = false)
    {
        $result = '';
        if ('' !== $content) {
            if ($type == self::ERROR) {
                $result = "\033[31m";
            } elseif ($type == self::WARNING) {
                $result = "\033[33m";
            } elseif ($type == self::LOG) {
                $result = "\033[32m";
            }

            $result .= $content;

            if ($type !== self::SIMPLE) {
                $result .= "\033[0m";
            }
        }

        if (true === $last) {
            $result .= PHP_EOL;
        }

        return $result;
    }

    /**
     * @param mixed  $data
     * @param string $prefix
     * @param string $suffix
     * @param string $filePath
     * @return string
     */
    private static function logFile($data, $prefix = '', $suffix = '', $filePath = null)
    {
        $row = $prefix;
        if (is_object($data) || is_array($data)) {
            $row .= var_export($data, true);
        } else {
            $row .= $data;
        }

        $row .= $suffix . PHP_EOL;

        if (null === $filePath) {
            $filePath = self::getDefaultFilePath();
        }

        self::createFile($filePath);

        if (is_writeable($filePath)) {
            file_put_contents($filePath, $row, FILE_APPEND);
        } else {
            self::log(
                __CLASS__ . '.php:' . __LINE__ . ' [Error]: Permission denied: ' . $filePath,
                false,
                self::ERROR
            );
        }
    }

    private static function createFile($filePath)
    {
        $fileHelper = new FileHelper();
        $fileHelper->createFile($filePath);

        if (FileHelper::errorInstance()->hasErrors()) {
            self::log(FileHelper::errorInstance()->getFirstError());
        }
    }

    /**
     * @return string
     */
    private static function getTrace()
    {
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);

        $trace = '';
        if (isset($debugBacktrace[self::$levelError]['file'], $debugBacktrace[self::$levelError]['line'])) {
            $trace = basename($debugBacktrace[self::$levelError]['file'])
                . ':'
                . $debugBacktrace[self::$levelError]['line']
                . ' ['
                . microtime(true)
                . ']: ';
        }

        return $trace;
    }

    /**
     * @return null|string
     */
    private static function getDefaultFilePath()
    {
        if (null === self::$filePath) {
            self::$filePath = __DIR__ . self::$defaultFilePath;
        }

        return self::$filePath;
    }
}
