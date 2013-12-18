<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

/**
 * Abstract logger handler
 */
abstract class ArkLoggerHandlerAbstract
{
    /**
     * Handler Options
     *  - level: Log levels can be handled by this handler
     *  - delay: If set to true, all logs will be processed with batchSend on destruction 
     * @var Array
     */
    protected $options;

    protected $logs = array();

    public function __construct($options = array())
    {
        if (isset($options['level'])) {
            if (is_array($options['level'])) {
                $options['levelInteger'] = array();
                foreach ($options['level'] as $level) {
                    $options['levelInteger'][] = ArkLogger::$levels[$level];
                }
            } else {
                $options['levelInteger'] = ArkLogger::$levels[$options['level']];
            }
        } else {
            $options['levelInteger'] = 0;
        }
        $this->options = $options;
    }

    public function getOption($key, $default = null)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return $default;
    }

    protected function getTraceMessages($trace)
    {
        $messages = array();
        foreach ($trace as $entry) {
            // ignore current file
            if (isset($entry['file']) && $entry['file'] !== __FILE__) {
                $messages[] = $entry['file'].'#'.$entry['line'].' '.$entry['class'].$entry['type'].$entry['function'].'()';
            }
        }

        return $messages;
    }

    /**
     * Format log message
     * @param  array $log
     * @return string
     */
    protected function format($log)
    {
        $result = date('Y-m-d H:i:s')."\t".$log['level']."\t".$log['message'];
        if (isset($log['trace'])) {
            $messages = $this->getTraceMessages($log['trace']);
            $result .= "\n\t".implode("\n\t", $messages);
        }

        return $result;
    }

    /**
     * Should this level of log be handled.
     * @param  integer $levelInteger
     * @return boolean
     */
    public function checkLevel($levelInteger)
    {
        $level = $this->options['levelInteger'];
        return 
            (is_array($level) && in_array($levelInteger, $level))
            || 
            (is_integer($level) && $levelInteger >= $level);
    }

    /**
     * Add log to list
     * @param array $log
     */
    public function add($log)
    {
        $this->logs[] = $log;
    }

    /**
     * Process batch send if any
     */
    public function __destruct()
    {
        if ($this->logs) {
            $this->batchSend($this->logs);
        }
    }

    /**
     * Process the log
     * @param  array $log
     */
    abstract public function send($log);

    /**
     * Process batch send, override this method if needed.
     * @param  array $logs
     */
    public function batchSend($logs)
    {
        foreach ($logs as $log) {
            $this->send($log);
        }
    }
}

/**
 * Simple log handler implement
 */
class ArkLoggerHandlerEcho extends ArkLoggerHandlerAbstract
{
    public function send($log)
    {
        echo $this->format($log)."\n";
    }
}

/**
 * File log handler
 * options:
 *  - file
 */
class ArkLoggerHandlerFile extends ArkLoggerHandlerAbstract
{
    /**
     * {@inheritdoc}
     */
    public function send($log)
    {
        $text = $this->format($log);
        $filename = $this->options['file'];
        if($fh = fopen($filename, 'a')){
            fwrite($fh, $text."\n");
            fclose($fh);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function batchSend($logs)
    {
        $text = '';
        foreach ($logs as $log) {
            $text .= $this->format($log)."\n";
        }

        $filename = $this->options['file'];

        if ($fh = fopen($filename, 'a')) {
            fwrite($fh, $text);
            fclose($fh);
        }
    }
}

/**
 * error_log handler
 * options:
 *  - message_type
 *  - destination
 *  - extra_headers
 * @see http://php.net/manual/en/function.error-log.php
 */
class ArkLoggerHandlerErrorLog extends ArkLoggerHandlerAbstract
{
    public function send($log)
    {
        $text = $this->format($log);
        error_log($text, $this->getOption('message_type'), $this->getOption('destination'), $this->getOption('extra_headers'));
    }

    public function batchSend($logs)
    {
        if ($this->getOption('message_type') == 1 || $this->getOption('message_type') == 3) {
            // mail or file
            $text = array();
            foreach ($logs as $log) {
                $text[] = $this->format($log);
            }
            error_log(implode("\n", $text), $this->getOption('message_type'), $this->getOption('destination'), $this->getOption('extra_headers'));
        } else {
            return parent::batchSend($logs);
        }
    }
}

/**
 * Simple logger library
 */
class ArkLogger
{
    /**
     * Log levels defination
     * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#3-psrlogloggerinterface
     * @var array
     */
    static $levels = array(
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7,
    );

    protected $handlers;

    public function __construct($handlers = array())
    {
        $this->handlers = $handlers;
    }

    public function addHandler($handler)
    {
        $this->handlers[] = $handler;
    }

    public function emergency($message, $trace = false)
    {
        $this->log('emergency', $message, $trace);
    }

    public function alert($message, $trace = false)
    {
        $this->log('alert', $message, $trace);
    }

    public function critical($message, $trace = false)
    {
        $this->log('critical', $message, $trace);
    }

    public function error($message, $trace = false)
    {
        $this->log('error', $message, $trace);
    }

    public function warning($message, $trace = false)
    {
        $this->log('warning', $message, $trace);
    }

    public function notice($message, $trace = false)
    {
        $this->log('notice', $message, $trace);
    }

    public function info($message, $trace = false)
    {
        $this->log('info', $message, $trace);
    }

    public function debug($message, $trace = false)
    {
        $this->log('debug', $message, $trace);
    }

    public function log($level, $message, $trace = false)
    {
        $levelInteger = self::$levels[$level];
        foreach ($this->handlers as $k => $handler) {
            if (is_array($handler)) {
                $class = $handler['class'];
                unset($handler['class']);
                $handler = $this->handlers[$k] = new $class($handler);
            }

            if ($handler->checkLevel($levelInteger)) {
                $log = array(
                    'message' => $message,
                    'level' => $level,
                    'time' => microtime(true),
                );

                if ($trace) {
                    $log['trace'] = debug_backtrace();
                }

                if ($handler->getOption('delay', false)) {
                    $handler->add($log);
                } else {
                    $handler->send($log);
                }
            }
        }
    }
}