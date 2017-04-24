<?php


namespace Office365\PHP\Client\SharePoint\Publishing;


use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\HttpMethod;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;
use Office365\PHP\Client\SharePoint\ClientContext;


class VideoItem extends ClientObject
{


    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry);
    }

    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }

    public function saveBinaryStream($content){
        $ctx = $this->getContext();
        $methodName = "GetFile()/SaveBinaryStream";
        $requestUrl = $this->getResourceUrl() . "/" . $methodName;
        $request = new RequestOptions($requestUrl);
        $request->Method = HttpMethod::Post;
        $request->Data = $content;
        if($ctx instanceof ClientContext)
            $ctx->ensureFormDigest($request);
        $response = $ctx->executeQueryDirect($request);
    }


    function setProperty($name, $value, $persistChanges = true)
    {
        if($name == "ID"){
            if(is_null($this->getResourcePath()))
                $this->setResourceUrl($this->parentCollection->getResourcePath()->toUrl() . "(guid'{$value}')");
            $this->{$name} = $value;
        }
        else
            parent::setProperty($name, $value, $persistChanges);
    }


    public function getEntityTypeName()
    {
        return implode(".",array("SP","Publishing",parent::getEntityTypeName()));
    }



}