<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

/**
 * Logger base class
 */
abstract class ArkLoggerBase
{
    static $levels = array(
        'trace' => 0,
        'debug' => 1,
        'info' => 2,
        'warn' => 3,
        'error' => 4,
        'fatal' => 5,
    );

    protected $messages = array();

    /**
     * Logger options
     *  - level
     *  - batch
     * @var array
     */
    protected $options = array();

    public function __construct($options = array()) {
        if(!isset($options['level']) || !isset(self::$levels[$options['level']])){
            $options['level'] = 0;
        }
        else{
            $options['level'] = self::$levels[$options['level']];
        }
        $this->options = $options;
    }

    public function __destruct()
    {
        foreach($this->messages as $message){
            $this->write($message[0], $message[1], $message[2]);
        }
    }

    abstract protected function write($message, $level, $time);

    protected function formatTrace($trace)
    {
        return $trace['file'].'#'.$trace['line'].' '.$trace['class'].$trace['type'].$trace['function'].'()';
    }

    protected function formatMessage($message, $level, $time)
    {
        return date('Y-m-d H:i:s', $time)."\t".$level."\t".$message;
    }

    public function log($message, $level, $trace = false)
    {
        if(self::$levels[$level] >= $this->options['level']){
            if($trace){
                $trace_messages = array();
                foreach(debug_backtrace() as $t){
                    if(isset($t['file']) && $t['file'] != __FILE__){
                        $trace_messages[] = $this->formatTrace($t);
                    }
                }
                $message.="\n\t".implode("\n\t", $trace_messages);
            }
            $message_entry = array($message, $level, time());
            if(isset($this->options['delay']) && $this->options['delay']){
                $this->messages[] = $message_entry;
            }
            else{
                $this->write($message_entry[0], $message_entry[1], $message_entry[2]);
            }
        }
    }

    public function trace($message)
    {
        $this->log($message, 'trace', true);
    }

    public function debug($message, $trace = false)
    {
        $this->log($message, 'debug', $trace);
    }

    public function info($message, $trace = false)
    {
        $this->log($message, 'info', $trace);
    }

    public function warn($message, $trace = false)
    {
        $this->log($message, 'warn', $trace);
    }

    public function error($message, $trace = false)
    {
        $this->log($message, 'error', $trace);
    }

    public function fatal($message, $trace = false)
    {
        $this->log($message, 'fatal', $trace);
    }
}

/**
 * File logger
 * Additional options:
 *  - file(log file path)
 *  - filesize(max filesize of log file)
 *  @todo Use new log file when current log file is full;
 *        support deplay writing
 */
class ArkLoggerFile extends ArkLoggerBase
{
    protected function write($message, $level, $time)
    {
        $filename = $this->options['file'];
        if($fh = fopen($filename, 'a')){
            fwrite($fh, $this->formatMessage($message, $level, $time)."\n");
            fclose($fh);
        }
    }
}