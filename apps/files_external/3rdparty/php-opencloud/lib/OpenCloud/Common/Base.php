<?php
/**
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 * @author Jamie Hannaford <jamie.hannaford@rackspace.com>
 */

namespace OpenCloud\Common;

use OpenCloud\Common\Lang;
use OpenCloud\Common\Exceptions\AttributeError;
use OpenCloud\Common\Exceptions\JsonError;
use OpenCloud\Common\Exceptions\UrlError;

/**
 * The root class for all other objects used or defined by this SDK.
 *
 * It contains common code for error handling as well as service functions that
 * are useful. Because it is an abstract class, it cannot be called directly,
 * and it has no publicly-visible properties.
 */
abstract class Base
{

    private $http_headers = array();
    private $_errors = array();

    /**
     * Debug status.
     *
     * @var    LoggerInterface
     * @access private
     */
    private $logger;

    /**
     * Sets the Logger object.
     * 
     * @param \OpenCloud\Common\Log\LoggerInterface $logger
     */
    public function setLogger(Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the Logger object.
     * 
     * @return \OpenCloud\Common\Log\AbstractLogger
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->setLogger(new Log\Logger);
        }
        return $this->logger;
    }

    /**
     * Returns the URL of the service/object
     *
     * The assumption is that nearly all objects will have a URL; at this
     * base level, it simply throws an exception to enforce the idea that
     * subclasses need to define this method.
     *
     * @throws UrlError
     */
    public function url($subresource = '')
    {
        throw new UrlError(Lang::translate(
            'URL method must be overridden in class definition'
        ));
    }

/**
     * Populates the current object based on an unknown data type.
     * 
     * @param  array|object|string|integer $info
     * @throws Exceptions\InvalidArgumentError
     */
    public function populate($info, $setObjects = true)
    {
        if (is_string($info) || is_integer($info)) {
            
            // If the data type represents an ID, the primary key is set
            // and we retrieve the full resource from the API
            $this->{$this->primaryKeyField()} = (string) $info;
            $this->refresh($info);
            
        } elseif (is_object($info) || is_array($info)) {
            
            foreach($info as $key => $value) {
                
                if ($key == 'metadata' || $key == 'meta') {
                    
                    if (empty($this->metadata) || !$this->metadata instanceof Metadata) {
                        $this->metadata = new Metadata;
                    }
                    
                    // Metadata
                    $this->$key->setArray($value);
                    
                } elseif (!empty($this->associatedResources[$key]) && $setObjects === true) {
                    
                    // Associated resource
                    try {
                        $resource = $this->service()->resource($this->associatedResources[$key], $value);
                        $resource->setParent($this);
                        $this->$key = $resource;
                    } catch (Exception\ServiceException $e) {}
                    
                } elseif (!empty($this->associatedCollections[$key]) && $setObjects === true) {
                    
                    // Associated collection
                    try {
                        $this->$key = $this->service()->resourceList($this->associatedCollections[$key], null, $this); 
                    } catch (Exception\ServiceException $e) {}
                    
                } else {
                    
                    // Normal key/value pair
                    $this->$key = $value; 
                }
            }
        } elseif (null !== $info) {
            throw new Exceptions\InvalidArgumentError(sprintf(
                Lang::translate('Argument for [%s] must be string or object'), 
                get_class()
            ));
        }
    }
    
    /**
     * Sets extended attributes on an object and validates them
     *
     * This function is provided to ensure that attributes cannot
     * arbitrarily added to an object. If this function is called, it
     * means that the attribute is not defined on the object, and thus
     * an exception is thrown.
     *
     * @codeCoverageIgnore
     * 
     * @param string $property the name of the attribute
     * @param mixed $value the value of the attribute
     * @return void
     */
    public function __set($property, $value)
    {
        $this->setProperty($property, $value);
    }

    /**
     * Sets an extended (unrecognized) property on the current object
     *
     * If RAXSDK_STRICT_PROPERTY_CHECKS is TRUE, then the prefix of the
     * property name must appear in the $prefixes array, or else an
     * exception is thrown.
     *
     * @param string $property the property name
     * @param mixed $value the value of the property
     * @param array $prefixes optional list of supported prefixes
     * @throws \OpenCloud\AttributeError if strict checks are on and
     *      the property prefix is not in the list of prefixes.
     */
    public function setProperty($property, $value, array $prefixes = array())
    {
        // if strict checks are off, go ahead and set it
        if (!RAXSDK_STRICT_PROPERTY_CHECKS 
            || $this->checkAttributePrefix($property, $prefixes)
        ) {
            $this->$property = $value;
        } else {
            // if that fails, then throw the exception
            throw new AttributeError(sprintf(
                Lang::translate('Unrecognized attribute [%s] for [%s]'),
                $property,
                get_class($this)
            ));
        }
    }

    /**
     * Converts an array of key/value pairs into a single query string
     *
     * For example, array('A'=>1,'B'=>2) would become 'A=1&B=2'.
     *
     * @param array $arr array of key/value pairs
     * @return string
     */
    public function makeQueryString($array)
    {
        $queryString = '';

        foreach($array as $key => $value) {
            if ($queryString) {
                $queryString .= '&';
            }
            $queryString .= urlencode($key) . '=' . urlencode($this->to_string($value));
        }

        return $queryString;
    }

    /**
     * Checks the most recent JSON operation for errors
     *
     * This function should be called after any `json_*()` function call.
     * This ensures that nasty JSON errors are detected and the proper
     * exception thrown.
     *
     * Example:
     *   `$obj = json_decode($string);`
     *   `if (check_json_error()) do something ...`
     *
     * @return boolean TRUE if an error occurred, FALSE if none
     * @throws JsonError
     * 
     * @codeCoverageIgnore
     */
    public function checkJsonError()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return;
            case JSON_ERROR_DEPTH:
                $jsonError = 'JSON error: The maximum stack depth has been exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $jsonError = 'JSON error: Invalid or malformed JSON';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $jsonError = 'JSON error: Control character error, possibly incorrectly encoded';
                break;
            case JSON_ERROR_SYNTAX:
                $jsonError = 'JSON error: Syntax error';
                break;
            case JSON_ERROR_UTF8:
                $jsonError = 'JSON error: Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $jsonError = 'Unexpected JSON error';
                break;
        }
        
        if (isset($jsonError)) {
            throw new JsonError(Lang::translate($jsonError));
        }
    }

    /**
     * Returns a class that implements the HttpRequest interface.
     *
     * This can be stubbed out for unit testing and avoid making live calls.
     */
    public function getHttpRequestObject($url, $method = 'GET', array $options = array())
    {
        return new Request\Curl($url, $method, $options);
    }

    /**
     * Checks the attribute $property and only permits it if the prefix is
     * in the specified $prefixes array
     *
     * This is to support extension namespaces in some services.
     *
     * @param string $property the name of the attribute
     * @param array $prefixes a list of prefixes
     * @return boolean TRUE if valid; FALSE if not
     */
    private function checkAttributePrefix($property, array $prefixes = array())
    {
        $prefix = strstr($property, ':', true);

        if (in_array($prefix, $prefixes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Converts a value to an HTTP-displayable string form
     *
     * @param mixed $x a value to convert
     * @return string
     */
    private function to_string($x)
    {
        if (is_bool($x) && $x) {
            return 'True';
        } elseif (is_bool($x)) {
            return 'False';
        } else {
            return (string) $x;
        }
    }

}
