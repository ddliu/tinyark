<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

namespace ddliu\tinyark;

/**
 * Ark app
 */
abstract class App
{
    protected $container;

    public $configs;

    public static $instance;

    public $event;

    public function __construct(array $configs)
    {
        $this->configs = $configs;

        if ($configs['debug']) {
            ini_set('display_errors', true);
            error_reporting(E_ALL^E_NOTICE);
        }
        
        $this->event = new ArkEventManager();

        $this->event->dispatch('app.before', $this);

        self::$instance = $this;
        
        //Service container
        $this->container = new ArkContainer($this->config->get('service', array()));
        
        // exception handler
        set_exception_handler(array($this, 'handleException'));
        $this->event->attach('app.exception', array($this, 'handleExceptionDefault'), true, ArkEventManager::PRIORITY_LOWEST);

        $this->init();
        //app is ready
        $this->event->dispatch('app.ready', $this);
    }
    
    /**
     * Init app
     */
    abstract protected function init();

    public function getContainer()
    {
        return $this->container;
    }

    public function isCli(){
        return PHP_SAPI === 'cli';
    }

    /**
     * Run app
     */
    abstract public function run();

    public function handleException($exception)
    {
        $this->dispatchResponseEvent('app.exception', $this, array(
            'exception' => $exception
        ));
    }

    public function dispatchResponseEvent($event, $source = null, $data = array())
    {
        if (is_string($event)) {
            $event = new ArkEvent($event, $source, $data);
        }
        $this->event->dispatch($event, $source, $data);

        if ($event->result !== null) {
            $this->respond($event->result);
        }
    }

    public function handleExceptionDefault($exception)
    {
        if (ARK_APP_DEBUG) {
            throw $exception;
        } else {
            echo 'Error occurred';
        }

        $this->respond($resonse);
    }

    /**
     * Respond and exit
     * @param  mixed $response
     */
    public function respond($response, $exit = true)
    {
        // response
        $event = new ArkEvent('app.response', $this, $response);
        $this->event->dispatch($event);

        echo $response;

        $this->event->dispatch('app.shutdown', $this);

        $exit && exit();
    }
}