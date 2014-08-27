<?php

namespace Guzzle\Service\Command\LocationVisitor\Request;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Description\Parameter;

/**
 * Visitor used to apply a parameter to an array that will be serialized as a top level key-value pair in a JSON body
 */
class JsonVisitor extends AbstractRequestVisitor
{
    /** @var bool Whether or not to add a Content-Type header when JSON is found */
    protected $jsonContentType = 'application/json';

    /** @var \SplObjectStorage Data object for persisting JSON data */
    protected $data;

    public function __construct()
    {
        $this->data = new \SplObjectStorage();
    }

    /**
     * Set the Content-Type header to add to the request if JSON is added to the body. This visitor does not add a
     * Content-Type header unless you specify one here.
     *
     * @param string $header Header to set when JSON is added (e.g. application/json)
     *
     * @return self
     */
    public function setContentTypeHeader($header = 'application/json')
    {
        $this->jsonContentType = $header;

        return $this;
    }

    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value)
    {
        if (isset($this->data[$command])) {
            $json = $this->data[$command];
        } else {
            $json = array();
        }
        $json[$param->getWireName()] = $this->prepareValue($value, $param);
        $this->data[$command] = $json;
    }

    public function after(CommandInterface $command, RequestInterface $request)
    {
        if (isset($this->data[$command])) {
            // Don't overwrite the Content-Type if one is set
            if ($this->jsonContentType && !$request->hasHeader('Content-Type')) {
                $request->setHeader('Content-Type', $this->jsonContentType);
            }

            $request->setBody(json_encode($this->data[$command]));
            unset($this->data[$command]);
        }
    }
}
