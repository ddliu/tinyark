<?php
class AView
{
    private $helpers = array();

    /**
     * One session for one render
     * @var array
     */
    private $sessions = array();

    /**
     * Page properties
     * Common properties:
     *  title: page title
     *  keywords: page keywords
     *  description: page description
     */
    private $properties = array();

    protected $options = array(
        'ext' => '.php',
        'extract' => true, //extract variables
    );

    public function __construct($options = null){
        if(null !== $options){
            $this->options = $options;
        }
        //register buildin helpers
        $this->registerHelper('core', 'AViewCoreHelper');
        $this->registerHelper('html', 'AViewHtmlHelper');
        $this->startSession();
    }

    private function startSession(){
        $this->sessions[] = new AViewSession();
    }

    private function endSession(){
        array_pop($this->sessions);
        if(!isset($this->sessions[0])){
            $this->startSession();
        }
    }

    private function getCurrentSession(){
        return end($this->sessions);
    }
    
    /**
     * Get path of view file
     * @param string $name view name
     * @return string
     */
    public function getViewFile($name){
		$path = '';
		if(isset($this->options['dir'])){
			$path.=$this->options['dir'].'/';
		}
		
		$path.=$name;
		if(isset($this->options['ext'])){
			$path.=$this->options['ext'];
		}
        return $path;
    }

    /**
     * Assign view variable
     * @param string|array $key
     * @param mixed $value
     */
    public function assign($key, $value = null){
        $this->getCurrentSession()->assign($key, $value);
    }

    public function getVariables(){
        return $this->getCurrentSession()->getVariables();
    }

    public function getVar($key, $default = null){
        return $this->getCurrentSession()->getVar($key, $default);
    }

    public function hasVar($key){
        return $this->getCurrentSession()->hasVar($key);
    }
    
    /**
     * Get page property
     * @param string $key property name
     * @param mixed $default default value
     */
    public function getProperty($key, $default = null){
        return isset($this->properties[$key])?$this->properties[$key]:$default;
    }
    
    /**
     * Set page property
     */
    public function setProperty($key, $value){
        $this->properties[$key] = $value;
    }

    public function hasProperty($key){
        return isset($this->properties[$key]);
    }

    public function extend($parent){
        $this->getCurrentSession()->addInherit($parent);
        ob_start();
    }

    public function block($blockname, $options = null){
        $this->getCurrentSession()->addBlock($blockname);
        ob_start();
    }

    public function beginBlock($blockname, $options = null){
        $this->block($blockname, $options);
    }

    public function getBlock($name){
        return $this->getCurrentSession()->getBlock($name);
    }

    public function endBlock(){
        $currentSession = $this->getCurrentSession();
        if(!$currentSession->hasBlocks()){
            throw new \LogicException('Block does not match');
        }
        $blockname = $currentSession->popBlock();
        $content = ob_get_clean();
        if(!$currentSession->hasBlock($blockname)){
            $currentSession->setBlock($blockname, $content);
        }
        //print block if it's top level OR it's a sub block
        if($currentSession->isTopLevel() || $currentSession->hasBlocks()){
            echo $currentSession->getBlock($blockname);
        }
    }

    public function registerHelper($name, $helper){
        $this->helpers[$name] = $helper;
    }

    public function getHelper($name){
        if(!isset($this->helpers[$name])){
            throw new Exception(sprintf('Helper "%s" does not exist', $name));
        }
        $helper = $this->helpers[$name];
        //class name
        if(is_string($helper)){
            $helper = new $helper($this);
            $this->helpers[$name] = $helper;
        }

        return $helper;
    }

    public function __call($method, $arguments){
        foreach($this->helpers as $k => $v){
            $helper = $this->getHelper($k);
            if(method_exists($helper, $method)){
                return call_user_func_array(array($helper, $method), $arguments);
            }
        }

        throw new Exception(sprintf('Helper method "%s" does not exist', $method));
    }

    public function clear(){
        while(ob_get_level()){
            ob_end_clean();
            //ob_end_flush();
        }
    }

    public function render($_name, $_variables = array(), $_return = false){
        if($this->getCurrentSession()->isRendered()){
            $this->startSession();
        }
        $this->getCurrentSession()->setRendered(true);

        //$this->resetView();
        if($_return){
            ob_start();
        }

        $this->assign($_variables);

        if($this->options['extract']){
            extract($this->getCurrentSession()->getVariables(), EXTR_SKIP);
        }
        require($this->getViewFile($_name));
        if($this->getCurrentSession()->hasBlocks()){
            throw new \LogicException('Block does not match');
        }
        $this->renderInherits();

        $this->endSession();
        if($_return){
            return ob_get_clean();
        }
    }

    protected function renderInherits(){
        $currentSession = $this->getCurrentSession();
        if($currentSession->hasInherits()){
            if(!$currentSession->isTopLevel()){
                ob_end_clean();
                $_viewName = $currentSession->getCurrentInherit();
                $currentSession->incCurrentInheritLevel();

                if($this->options['extract']){
                    extract($currentSession->getVariables(), EXTR_SKIP);
                }

                require($this->getViewFile($_viewName));

                if($currentSession->hasBlocks()){
                    throw new \LogicException('Block does not match');
                }
                
                $this->renderInherits();
            }
            else{
                if(ob_get_level()){
                    ob_end_flush();
                }
            }
        }
    }
}

class AViewSession
{
    /**
     * Template variables
     */
    private $variables = array();

    private $blockStack = array();

    private $inherits = array();

    private $currentInheritLevel = 0;

    private $blocks = array();

    private $rendered = false;

    /**
     * Assign view variable
     * @param string|array $key
     * @param mixed $value
     */
    public function assign($key, $value = null){
        if(is_array($key)){
            foreach($key as $k => $v){
                $this->variables[$k] = $v;
            }
        }
        else{
            $this->variables[$key] = $value;
        }
    }

    public function getVar($key, $default = null){
        if(isset($this->variables[$key])){
            return $this->variables[$key];
        }
        $parts = explode('.', $key);
        $parent = $this->variables;
        foreach($parts as $part){
            if(!isset($parent[$part])){
                return $default;
            }
            $parent = $parent[$part];
        }
        return $parent;
    }

    public function hasVar($key){
        return isset($this->variables[$key]);
    }

    public function getVariables(){
        return $this->variables;
    }

    public function setRendered($rendered){
        $this->rendered = $rendered;
    }

    public function isRendered(){
        return $this->rendered;
    }

    public function extend($parent){
        ob_start();
        $this->inherits[] = $parent;
        //$this->currentInheritLevel++;
    }

    public function hasBlocks(){
        return isset($this->blockStack[0]);
    }

    public function hasBlock($blockname){
        return isset($this->blocks[$blockname]);
    }

    public function addBlock($blockname){
        $this->blockStack[] = $blockname;
    }

    public function setBlock($blockname, $value){
        $this->blocks[$blockname] = $value;
    }

    public function popBlock(){
        return array_pop($this->blockStack);
    }

    public function getBlock($name){
        return isset($this->blocks[$name])?$this->blocks[$name]:'';
    }

    public function isTopLevel(){
        return $this->currentInheritLevel >= count($this->inherits);
    }

    public function addInherit($parent){
        $this->inherits[] = $parent;
    }

    public function hasInherits(){
        return isset($this->inherits[0]);
    }

    public function incCurrentInheritLevel(){
        $this->currentInheritLevel++;
    }

    public function getCurrentInheritLevel(){
        return $this->currentInheritLevel;
    }

    public function getCurrentInherit(){
        return $this->inherits[$this->currentInheritLevel];
    }
}

abstract class AViewHelper
{
    protected $view;

    public function __construct($view){
        $this->view = $view;
    }
}

/**
 * Core view helpers
 */
class AViewCoreHelper extends AViewHelper
{
    protected $captureVar = false;
    protected $captures = array();
    protected $filter = null;

    /**
     * Escape content with htmlspecialchars
     * @param  string $string content to be escaped
     * @return string         escaped content
     */
    public function escape($string){
        return htmlspecialchars($string);
    }

    /**
     * Match two variable, return $value if success, $alternate or else
     * @param  mixed $a 
     * @param  mixed $b
     * @param  mixed $value
     * @param  mixed $alternate
     * @return mixed
     */
    public function match($a, $b, $value, $alternate = ''){
        return $a == $b?$value:$alternate;
    }

    /**
     * Match two variable, return first class(wraped with class="") if success, $alternate or else
     * @param  mixed $a
     * @param  mixed $b
     * @param  string $class
     * @param  string $alternate
     * @return string
     */
    public function matchClass($a, $b, $class, $alternate = null){
        return 
            $a == $b?
                (' class="'.$class.'" ')
                :
                (
                $alternate?
                    (' class="'.$alternate.'" ')
                    :
                    ''
                )
        ;
    }

    /**
     * begin to capture block of content(based on output buffer)
     * @param  mixed $var variable name to save to
     * @throws Ark\View\Exception If nested capture found
     */
    public function capture($var = null){
        if(false !== $this->captureVar){
            throw new Exception('Nested capture is not supported');
        }
        ob_start();
        $this->captureVar = $var;
    }
    
    public function beginCapture($var = null){
		$this->capture($var);
	}

    /**
     * Finish capture block of content
     * @throws Ark\View\Exception If capture is not started
     */
    public function endCapture(){
        if(false === $this->captureVar){
            throw new Exception('Capture is not started');
        }
        $data = ob_get_clean();
        if(null === $this->captureVar){
            $this->captures[] = $data;
        }
        else{
            $this->captures[$this->captureVar] = $data;
        }
        $this->captureVar = false;
    }

    /**
     * Get captured content
     * @param  mixed $var variable name
     * @return string   captured content
     */
    public function getCapture($var = null){
        if(null === $var){
            if(count($this->captures)){
                return end($this->captures);
            }
            else{
                return '';
            }
        }
        else{
            return isset($this->captures[$var])?$this->captures[$var]:'';
        }
    }
    
    public function beginFilter($name){
		$this->filter = $name;
		$this->beginCapture('filter');
	}
	
	public function endFilter(){
		$this->endCapture();
		$content = $this->getCapture('filter');
		echo call_user_func($this->filter, $content);
		$this->filter = null;
	}
}

class AViewHtmlHelper extends AViewHelper
{
    public function htmlOptions($options, $value = null){
        foreach($options as $k=>$v){
            $selected = ($k == $value)?' selected="selected"':'';
            echo '<option value="'.htmlspecialchars($k).'"'.$selected.'>'.htmlspecialchars($v).'</option>';
        }
    }
    public function htmlTag($tag, $options = null){
        $this->htmlBeginTag($tag, $options, true);
    }

    public function htmlBeginTag($tag, $options = null, $end = false){
        if(null !== $options){
            $properties = array();
            foreach ($options as $key => $value) {
                if(is_int($key)){
                    $properties[] = ' '.$value;
                }
                else{
                    $properties[] = ' '.$key.'="'.htmlspecialchars($value).'"';
                }
            }
        }
        echo '<'.$tag.(isset($properties)?implode('', $properties):'');
        if($end){
            echo ' />';
        }
        else{
            echo '>';
        }
    }

    public function htmlEndTag($tag){
        echo '</'.$tag.'>';
    }

    public function getName(){
        return 'html';
    }
}