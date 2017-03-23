<?php


namespace Office365\PHP\Client\Runtime;


use Office365\PHP\Client\Runtime\OData\ODataPathParser;

abstract class ResourcePath
{
    /**
     * ResourcePath constructor.
     * @param ClientRuntimeContext $context
     * @param ResourcePath|null $parent
     */
    public function __construct(ClientRuntimeContext $context, ResourcePath $parent = null)
    {
        $this->context = $context;
        $this->parent = $parent;
        $this->ServerObjectIsNull = true;
    }


    /**
     * @return ClientRuntimeContext
     */
    public function getContext(){
        return $this->context;
    }

    /**
     * @return null|ResourcePath
     */
    public function getParent(){
        return $this->parent;
    }


    /**
     * @param ClientRuntimeContext $context
     * @param string $value
     * @return null|ResourcePathEntity
     */
    public static function parse(ClientRuntimeContext $context, $value){

        $pathNames = ODataPathParser::parsePathString($value);
        $path = null;
        foreach ($pathNames as $pathName){
            $path = new ResourcePathEntity($context,$path,$pathName);
        }
        return $path;
    }


    /**
     * @return bool
     */
    public function isInitialized(){
        return !is_null($this->getName());
    }



    /**
     * @return string
     */
    public function toUrl()
    {
        $paths = array();
        $current = clone $this;
        while (isset($current)) {
            array_unshift($paths, $current->getName());
            $current = $current->parent;
        }
        return implode("/", $paths);
    }


    /**
     * @param string $url
     */
    public function fromUrl($url){

    }

    
    public abstract function getName();


    /**
     * @var ResourcePath
     */
    protected $parent;


    /**
     * @var ClientRuntimeContext
     */
    protected $context;


    /**
     * @var bool
     */
    public $ServerObjectIsNull;

}