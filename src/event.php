<?php
class ArkEventManager
{
    protected $eventList = array();

    const PRIORITY_HIGHEST = 0;
    const PRIORITY_HIGH = 5;
    const PRIORITY_DEFAULT = 10;
    const PRIORITY_LOW = 100;
    const PRIORITY_LOWEST = 10000;

    public function attach($name, $callback, $passParam = false, $priority = null)
    {
        if(null === $priority){
            $priority = self::PRIORITY_DEFAULT;
        }

        $this->eventList[$name][$priority][] = array(
            $callback,
            $passParam,
        );

        return $this;
    }

    public function detach($name, $callback = null)
    {
        if(null === $callback){
            if(isset($this->eventList[$name])){
                unset($this->eventList[$name]);
            }
        }
        elseif(null === $name){
            foreach($this->eventList as $event_name => $priorities){
                foreach($priorities as $priority => $callbacks){
                    foreach($callbacks as $k => $the_callback){
                        if($callback === $the_callback[0]){
                            unset($this->eventList[$event_name][$priority][$k]);
                            break;
                        }
                    }
                }
            }
        }
        else{
            if(isset($this->eventList[$name])){
                foreach($this->eventList[$name] as $priority => $callbacks){
                    foreach($callbacks as $k => $the_callback){
                        if($callback === $the_callback[0]){
                            unset($this->eventList[$name][$priority][$k]);
                            break;
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function dispatch($event, $source = null, $event_data = array())
    {
        $event_name = is_string($event)?$event:$event->getName();
        if(!isset($this->eventList[$event_name])){
            return;
        }

        if(is_string($event)){
            $event = new ArkEvent($event, $source, $event_data);
        }
        ksort($this->eventList[$event_name]);
        foreach ($this->eventList[$event_name] as $priorities) {
            foreach($priorities as $callback){
                //pass params
                if($callback[1]){
                    $result = call_user_func_array($callback[0], $event->data);
                }
                else{
                    $result = call_user_func($callback[0], $event);
                }

                if($result !== null){
                    $event->result = $result;
                }
                if(false === $result){
                    $event->stopPropagation();
                    break;
                }
                if($event->isStopped()){
                    break;
                }
            }
        }
    }
}


class ArkEvent
{
    protected $name;

    protected $source;

    public $data;

    public $result;

    protected $stopped;

    public function __construct($name, $source = null, $data = null)
    {
        $this->name = $name;
        $this->source = $source;
    }

    public function stopPropagation()
    {
        $this->stopped = true;
    }

    public function isStopped()
    {
        return $this->stopped;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSource()
    {
        return $this->source;
    }
}