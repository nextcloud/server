<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

use Sabre\Event\Loop;
use JsonMapper;
use JsonMapper_Exception;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

class Dispatcher
{
    /**
     * @var object
     */
    private $target;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * method => ReflectionMethod[]
     *
     * @var ReflectionMethod
     */
    private $methods;

    /**
     * @var \phpDocumentor\Reflection\DocBlockFactory
     */
    private $docBlockFactory;

    /**
     * @var \phpDocumentor\Reflection\Types\ContextFactory
     */
    private $contextFactory;

    /**
     * @param object $target    The target object that should receive the method calls
     * @param string $delimiter A delimiter for method calls on properties, for example someProperty->someMethod
     */
    public function __construct($target, $delimiter = '->')
    {
        $this->target = $target;
        $this->delimiter = $delimiter;
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new Types\ContextFactory();
        $this->mapper = new JsonMapper();
    }

    /**
     * Calls the appropriate method handler for an incoming Message
     *
     * @param string|object $msg The incoming message
     * @return mixed
     */
    public function dispatch($msg)
    {
        if (is_string($msg)) {
            $msg = json_decode($msg);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Error(json_last_error_msg(), ErrorCode::PARSE_ERROR);
            }
        }
        // Find out the object and function that should be called
        $obj = $this->target;
        $parts = explode($this->delimiter, $msg->method);
        // The function to call is always the last part of the method
        $fn = array_pop($parts);
        // For namespaced methods like textDocument/didOpen, call the didOpen method on the $textDocument property
        // For simple methods like initialize, shutdown, exit, this loop will simply not be entered and $obj will be
        // the target
        foreach ($parts as $part) {
            if (!isset($obj->$part)) {
                throw new Error("Method {$msg->method} is not implemented", ErrorCode::METHOD_NOT_FOUND);
            }
            $obj = $obj->$part;
        }
        if (!isset($this->methods[$msg->method])) {
            try {
                $method = new ReflectionMethod($obj, $fn);
                $this->methods[$msg->method] = $method;
            } catch (ReflectionException $e) {
                throw new Error($e->getMessage(), ErrorCode::METHOD_NOT_FOUND, null, $e);
            }
        }
        $method = $this->methods[$msg->method];
        $parameters = $method->getParameters();
        if ($method->getDocComment()) {
            $docBlock = $this->docBlockFactory->create(
                $method->getDocComment(),
                $this->contextFactory->createFromReflector($method->getDeclaringClass())
            );
            $paramTags = $docBlock->getTagsByName('param');
        }
        $args = [];
        if (isset($msg->params)) {
            // Find out the position
            if (is_array($msg->params)) {
                $args = $msg->params;
            } else if (is_object($msg->params)) {
                foreach ($parameters as $pos => $parameter) {
                    $value = null;
                    foreach(get_object_vars($msg->params) as $key => $val) {
                        if ($parameter->name === $key) {
                            $value = $val;
                            break;
                        }
                    }
                    $args[$pos] = $value;
                }
            } else {
                throw new Error('Params must be structured or omitted', ErrorCode::INVALID_REQUEST);
            }
            foreach ($args as $position => $value) {
                try {
                    // If the type is structured (array or object), map it with JsonMapper
                    if (is_object($value)) {
                        // Does the parameter have a type hint?
                        $param = $parameters[$position];
                        if ($param->hasType()) {
                            $paramType = $param->getType();
                            if ($paramType instanceof ReflectionNamedType) {
                                // We have object data to map and want the class name.
                                // This should not include the `?` if the type was nullable.
                                $class = $paramType->getName();
                            } else {
                                // Fallback for php 7.0, which is still supported (and doesn't have nullable).
                                $class = (string)$paramType;
                            }
                            $value = $this->mapper->map($value, new $class());
                        }
                    } else if (is_array($value) && isset($docBlock)) {
                        // Get the array type from the DocBlock
                        $type = $paramTags[$position]->getType();
                        // For union types, use the first one that is a class array (often it is SomeClass[]|null)
                        if ($type instanceof Types\Compound) {
                            for ($i = 0; $t = $type->get($i); $i++) {
                                if (
                                    $t instanceof Types\Array_
                                    && $t->getValueType() instanceof Types\Object_
                                    && (string)$t->getValueType() !== 'object'
                                ) {
                                    $class = (string)$t->getValueType()->getFqsen();
                                    $value = $this->mapper->mapArray($value, [], $class);
                                    break;
                                }
                            }
                        } else if ($type instanceof Types\Array_) {
                            $class = (string)$type->getValueType()->getFqsen();
                            $value = $this->mapper->mapArray($value, [], $class);
                        } else {
                            throw new Error('Type is not matching @param tag', ErrorCode::INVALID_PARAMS);
                        }
                    }
                } catch (JsonMapper_Exception $e) {
                    throw new Error($e->getMessage(), ErrorCode::INVALID_PARAMS, null, $e);
                }
                $args[$position] = $value;
            }
        }
        ksort($args);
        $result = $obj->$fn(...$args);
        return $result;
    }
}
