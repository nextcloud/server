<?php

namespace Guzzle\Service\Command\LocationVisitor;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Service\Command\LocationVisitor\Request\RequestVisitorInterface;
use Guzzle\Service\Command\LocationVisitor\Response\ResponseVisitorInterface;

/**
 * Flyweight factory used to instantiate request and response visitors
 */
class VisitorFlyweight
{
    /** @var self Singleton instance of self */
    protected static $instance;

    /** @var array Default array of mappings of location names to classes */
    protected static $defaultMappings = array(
        'request.body'          => 'Guzzle\Service\Command\LocationVisitor\Request\BodyVisitor',
        'request.header'        => 'Guzzle\Service\Command\LocationVisitor\Request\HeaderVisitor',
        'request.json'          => 'Guzzle\Service\Command\LocationVisitor\Request\JsonVisitor',
        'request.postField'     => 'Guzzle\Service\Command\LocationVisitor\Request\PostFieldVisitor',
        'request.postFile'      => 'Guzzle\Service\Command\LocationVisitor\Request\PostFileVisitor',
        'request.query'         => 'Guzzle\Service\Command\LocationVisitor\Request\QueryVisitor',
        'request.response_body' => 'Guzzle\Service\Command\LocationVisitor\Request\ResponseBodyVisitor',
        'request.responseBody'  => 'Guzzle\Service\Command\LocationVisitor\Request\ResponseBodyVisitor',
        'request.xml'           => 'Guzzle\Service\Command\LocationVisitor\Request\XmlVisitor',
        'response.body'         => 'Guzzle\Service\Command\LocationVisitor\Response\BodyVisitor',
        'response.header'       => 'Guzzle\Service\Command\LocationVisitor\Response\HeaderVisitor',
        'response.json'         => 'Guzzle\Service\Command\LocationVisitor\Response\JsonVisitor',
        'response.reasonPhrase' => 'Guzzle\Service\Command\LocationVisitor\Response\ReasonPhraseVisitor',
        'response.statusCode'   => 'Guzzle\Service\Command\LocationVisitor\Response\StatusCodeVisitor',
        'response.xml'          => 'Guzzle\Service\Command\LocationVisitor\Response\XmlVisitor'
    );

    /** @var array Array of mappings of location names to classes */
    protected $mappings;

    /** @var array Cache of instantiated visitors */
    protected $cache = array();

    /**
     * @return self
     * @codeCoverageIgnore
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param array $mappings Array mapping request.name and response.name to location visitor classes. Leave null to
     *                        use the default values.
     */
    public function __construct(array $mappings = null)
    {
        $this->mappings = $mappings === null ? self::$defaultMappings : $mappings;
    }

    /**
     * Get an instance of a request visitor by location name
     *
     * @param string $visitor Visitor name
     *
     * @return RequestVisitorInterface
     */
    public function getRequestVisitor($visitor)
    {
        return $this->getKey('request.' . $visitor);
    }

    /**
     * Get an instance of a response visitor by location name
     *
     * @param string $visitor Visitor name
     *
     * @return ResponseVisitorInterface
     */
    public function getResponseVisitor($visitor)
    {
        return $this->getKey('response.' . $visitor);
    }

    /**
     * Add a response visitor to the factory by name
     *
     * @param string                  $name    Name of the visitor
     * @param RequestVisitorInterface $visitor Visitor to add
     *
     * @return self
     */
    public function addRequestVisitor($name, RequestVisitorInterface $visitor)
    {
        $this->cache['request.' . $name] = $visitor;

        return $this;
    }

    /**
     * Add a response visitor to the factory by name
     *
     * @param string                   $name    Name of the visitor
     * @param ResponseVisitorInterface $visitor Visitor to add
     *
     * @return self
     */
    public function addResponseVisitor($name, ResponseVisitorInterface $visitor)
    {
        $this->cache['response.' . $name] = $visitor;

        return $this;
    }

    /**
     * Get a visitor by key value name
     *
     * @param string $key Key name to retrieve
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function getKey($key)
    {
        if (!isset($this->cache[$key])) {
            if (!isset($this->mappings[$key])) {
                list($type, $name) = explode('.', $key);
                throw new InvalidArgumentException("No {$type} visitor has been mapped for {$name}");
            }
            $this->cache[$key] = new $this->mappings[$key];
        }

        return $this->cache[$key];
    }
}
