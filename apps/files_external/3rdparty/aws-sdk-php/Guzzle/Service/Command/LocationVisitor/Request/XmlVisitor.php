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
            $xml = $this->finishDocument($this->data[$command]);
            unset($this->data[$command]);
        } else {
            // Check if XML should always be sent for the command
            $operation = $command->getOperation();
            if ($operation->getData('xmlAllowEmpty')) {
                $xmlWriter = $this->createRootElement($operation);
                $xml = $this->finishDocument($xmlWriter);
            }
        }

        if ($xml) {
            // Don't overwrite the Content-Type if one is set
            if ($this->contentType && !$request->hasHeader('Content-Type')) {
                $request->setHeader('Content-Type', $this->contentType);
            }
            $request->setBody($xml);
        }
    }

    /**
     * Create the root XML element to use with a request
     *
     * @param Operation $operation Operation object
     *
     * @return \XMLWriter
     */
    protected function createRootElement(Operation $operation)
    {
        static $defaultRoot = array('name' => 'Request');
        // If no root element was specified, then just wrap the XML in 'Request'
        $root = $operation->getData('xmlRoot') ?: $defaultRoot;
        // Allow the XML declaration to be customized with xmlEncoding
        $encoding = $operation->getData('xmlEncoding');

        $xmlWriter = $this->startDocument($encoding);

        $xmlWriter->startElement($root['name']);
        // Create the wrapping element with no namespaces if no namespaces were present
        if (!empty($root['namespaces'])) {
            // Create the wrapping element with an array of one or more namespaces
            foreach ((array) $root['namespaces'] as $prefix => $uri) {
                $nsLabel = 'xmlns';
                if (!is_numeric($prefix)) {
                    $nsLabel .= ':'.$prefix;
                }
                $xmlWriter->writeAttribute($nsLabel, $uri);
            }
        }
        return $xmlWriter;
    }

    /**
     * Recursively build the XML body
     *
     * @param \XMLWriter $xmlWriter XML to modify
     * @param Parameter  $param     API Parameter
     * @param mixed      $value     Value to add
     */
    protected function addXml(\XMLWriter $xmlWriter, Parameter $param, $value)
    {
        if ($value === null) {
            return;
        }

        $value = $param->filter($value);
        $type = $param->getType();
        $name = $param->getWireName();
        $prefix = null;
        $namespace = $param->getData('xmlNamespace');
        if (false !== strpos($name, ':')) {
            list($prefix, $name) = explode(':', $name, 2);
        }

        if ($type == 'object' || $type == 'array') {
            if (!$param->getData('xmlFlattened')) {
                $xmlWriter->startElementNS(null, $name, $namespace);
            }
            if ($param->getType() == 'array') {
                $this->addXmlArray($xmlWriter, $param, $value);
            } elseif ($param->getType() == 'object') {
                $this->addXmlObject($xmlWriter, $param, $value);
            }
            if (!$param->getData('xmlFlattened')) {
                $xmlWriter->endElement();
            }
            return;
        }
        if ($param->getData('xmlAttribute')) {
            $this->writeAttribute($xmlWriter, $prefix, $name, $namespace, $value);
        } else {
            $this->writeElement($xmlWriter, $prefix, $name, $namespace, $value);
        }
    }

    /**
     * Write an attribute with namespace if used
     *
     * @param  \XMLWriter $xmlWriter XMLWriter instance
     * @param  string     $prefix    Namespace prefix if any
     * @param  string     $name      Attribute name
     * @param  string     $namespace The uri of the namespace
     * @param  string     $value     The attribute content
     */
    protected function writeAttribute($xmlWriter, $prefix, $name, $namespace, $value)
    {
        if (empty($namespace)) {
            $xmlWriter->writeAttribute($name, $value);
        } else {
            $xmlWriter->writeAttributeNS($prefix, $name, $namespace, $value);
        }
    }

    /**
     * Write an element with namespace if used
     *
     * @param  \XMLWriter $xmlWriter XML writer resource
     * @param  string     $prefix    Namespace prefix if any
     * @param  string     $name      Element name
     * @param  string     $namespace The uri of the namespace
     * @param  string     $value     The element content
     */
    protected function writeElement(\XMLWriter $xmlWriter, $prefix, $name, $namespace, $value)
    {
        $xmlWriter->startElementNS($prefix, $name, $namespace);
        if (strpbrk($value, '<>&')) {
            $xmlWriter->writeCData($value);
        } else {
            $xmlWriter->writeRaw($value);
        }
        $xmlWriter->endElement();
    }

    /**
     * Create a new xml writer and start a document
     *
     * @param  string $encoding document encoding
     *
     * @return \XMLWriter the writer resource
     */
    protected function startDocument($encoding)
    {
        $xmlWriter = new \XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startDocument('1.0', $encoding);

        return $xmlWriter;
    }

    /**
     * End the document and return the output
     *
     * @param \XMLWriter $xmlWriter
     *
     * @return \string the writer resource
     */
    protected function finishDocument($xmlWriter)
    {
        $xmlWriter->endDocument();

        return $xmlWriter->outputMemory();
    }

    /**
     * Add an array to the XML
     */
    protected function addXmlArray(\XMLWriter $xmlWriter, Parameter $param, &$value)
    {
        if ($items = $param->getItems()) {
            foreach ($value as $v) {
                $this->addXml($xmlWriter, $items, $v);
            }
        }
    }

    /**
     * Add an object to the XML
     */
    protected function addXmlObject(\XMLWriter $xmlWriter, Parameter $param, &$value)
    {
        $noAttributes = array();
        // add values which have attributes
        foreach ($value as $name => $v) {
            if ($property = $param->getProperty($name)) {
                if ($property->getData('xmlAttribute')) {
                    $this->addXml($xmlWriter, $property, $v);
                } else {
                    $noAttributes[] = array('value' => $v, 'property' => $property);
                }
            }
        }
        // now add values with no attributes
        foreach ($noAttributes as $element) {
            $this->addXml($xmlWriter, $element['property'], $element['value']);
        }
    }
}
