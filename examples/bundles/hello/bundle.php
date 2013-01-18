<?php
class HelloBundle extends ArkBundle
{
    public function initWeb()
    {
        parent::initWeb();
        
        $this->match('hello/<id:\d+>', function($id){
            return 'hello-'.$id;
        });

        $this->match('<controller:\w+>/<action>');
    }
}