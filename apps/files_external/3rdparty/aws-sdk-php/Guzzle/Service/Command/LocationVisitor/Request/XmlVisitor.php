<?php

namespace Guzzle\Service\Command\LocationVisitor\Request;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Description\Operation;
use Guzzle\Service\Description\Parameter;

/**
 * Location visitor used to serialize XML bodies
 */
class XmlVisitor extends AbstractRequestVisitor
{
    /** @var \SplObjectStorage Data object for persisting XML data */
    protected $data;

    /** @var bool Content-Type header added when XML is found */
    protected $contentType = 'application/xml';

    public function __construct()
    {
        $this->data = new \SplObjectStorage();
    }

    /**
     * Change the content-type header that is added when XML is found
     *
     * @param string $header Header to set when XML is found
     *
     * @return self
     */
    public function setContentTypeHeader($header)
    {
        $this->contentType = $header;

        return $this;
    }

    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value)
    {
        $xml = isset($this->data[$command])
            ? $this->data[$command]
            : $this->createRootElement($param->getParent());
        $this->addXml($xml, $param, $value);
        $this->data[$command] = $xml;
    }

    public function after(CommandInterface $command, RequestInterface $request)
    {
        $xml = null;

        // If data was found that needs to be serialized, then do so
        if (isset($this->data[$command])) {
            $xml = $this->data[$command]->asXML();
            unset($this->data[$command]);
        } else {
            // Check if XML should always be sent for the command
            $operation = $command->getOperation();
            if ($operation->getData('xmlAllowEmpty')) {
                $xml = $this->createRootElement($operation)->asXML();
            }
        }

        if ($xml) {
            $request->setBody($xml);
            // Don't overwrite the Content-Type if one is set
            if ($this->contentType && !$request->hasHeader('Content-Type')) {
                $request->setHeader('Content-Type', $this->contentType);
            }
        }
    }

    /**
     * Create the root XML element to use with a request
     *
     * @param Operation $operation Operation object
     *
     * @return \SimpleXMLElement
     */
    protected function createRootElement(Operation $operation)
    {
        static $defaultRoot = array('name' => 'Request');
        // If no root element was specified, then just wrap the XML in 'Request'
        $root = $operation->getData('xmlRoot') ?: $defaultRoot;

        // Allow the XML declaration to be customized with xmlEncoding
        $declaration = '<?xml version="1.0"';
        if ($encoding = $operation->getData('xmlEncoding')) {
            $declaration .= ' encoding="' . $encoding . '"';
        }
        $declaration .= "?>";

        // Create the wrapping element with no namespaces if no namespaces were present
        if (empty($root['namespaces'])) {
            return new \SimpleXMLElement("{$declaration}\n<{$root['name']}/>");
        } else {
            // Create the wrapping element with an array of one or more namespaces
            $xml = "{$declaration}\n<{$root['name']} ";
            foreach ((array) $root['namespaces'] as $prefix => $uri) {
                $xml .= is_numeric($prefix) ? "xmlns=\"{$uri}\" " : "xmlns:{$prefix}=\"{$uri}\" ";
            }
            return new \SimpleXMLElement($xml . "/>");
        }
    }

    /**
     * Recursively build the XML body
     *
     * @param \SimpleXMLElement $xml   XML to modify
     * @param Parameter         $param API Parameter
     * @param mixed             $value Value to add
     */
    protected function addXml(\SimpleXMLElement $xml, Parameter $param, $value)
    {
        if ($value === null) {
            return;
        }

        $value = $param->filter($value);
        $type = $param->getType();

        if ($type == 'object' || $type == 'array') {
            $ele = $param->getData('xmlFlattened') ? $xml : $xml->addChild($param->getWireName());
            if ($param->getType() == 'array') {
                $this->addXmlArray($ele, $param, $value, $param->getData('xmlNamespace'));
            } elseif ($param->getType() == 'object') {
                $this->addXmlObject($ele, $param, $value);
            }
        } elseif ($param->getData('xmlAttribute')) {
            $xml->addAttribute($param->getWireName(), $value, $param->getData('xmlNamespace'));
        } else {
            $xml->addChild($param->getWireName(), $value, $param->getData('xmlNamespace'));
        }
    }

    /**
     * Add an array to the XML
     */
    protected function addXmlArray(\SimpleXMLElement $xml, Parameter $param, &$value)
    {
        if ($items = $param->getItems()) {
            foreach ($value as $v) {
                $this->addXml($xml, $items, $v);
            }
        }
    }

    /**
     * Add an object to the XML
     */
    protected function addXmlObject(\SimpleXMLElement $xml, Parameter $param, &$value)
    {
        foreach ($value as $name => $v) {
            if ($property = $param->getProperty($name)) {
                $this->addXml($xml, $property, $v);
            }
        }
    }
}
