<?php
class ArkTwigFileSystemLoader extends Twig_Loader_Filesystem
{
    protected $locator;

    public function __construct($paths, $locator = null)
    {
        parent::__construct($paths);
        $this->locator = $locator;
    }

    protected function findTemplate($name)
    {
        if(null !== $this->locator){
            if(isset($this->cache[$name])){
                return $this->cache[$name];
            }

            if($path = call_user_func($this->locator, $name)){
                return $this->cache[$name] = $path;
            }
        }

        return parent::findTemplate($name);
    }
}