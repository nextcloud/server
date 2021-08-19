<?php
/* ===========================================================================
 * Copyright (c) 2018-2021 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

namespace Opis\Closure;

use Closure;
use Serializable;
use SplObjectStorage;
use ReflectionObject;

/**
 * Provides a wrapper for serialization of closures
 */
class SerializableClosure implements Serializable
{
    /**
     * @var Closure Wrapped closure
     *
     * @see \Opis\Closure\SerializableClosure::getClosure()
     */
    protected $closure;

    /**
     * @var ReflectionClosure A reflection instance for closure
     *
     * @see \Opis\Closure\SerializableClosure::getReflector()
     */
    protected $reflector;

    /**
     * @var mixed Used at deserialization to hold variables
     *
     * @see \Opis\Closure\SerializableClosure::unserialize()
     * @see \Opis\Closure\SerializableClosure::getReflector()
     */
    protected $code;

    /**
     * @var string Closure's ID
     */
    protected $reference;

    /**
     * @var string Closure scope
     */
    protected $scope;

    /**
     * @var ClosureContext Context of closure, used in serialization
     */
    protected static $context;

    /**
     * @var ISecurityProvider|null
     */
    protected static $securityProvider;

    /** Array recursive constant*/
    const ARRAY_RECURSIVE_KEY = '¯\_(ツ)_/¯';

    /**
     * Constructor
     *
     * @param   Closure $closure Closure you want to serialize
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
        if (static::$context !== null) {
            $this->scope = static::$context->scope;
            $this->scope->toserialize++;
        }
    }

    /**
     * Get the Closure object
     *
     * @return  Closure The wrapped closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Get the reflector for closure
     *
     * @return  ReflectionClosure
     */
    public function getReflector()
    {
        if ($this->reflector === null) {
            $this->reflector = new ReflectionClosure($this->closure);
            $this->code = null;
        }

        return $this->reflector;
    }

    /**
     * Implementation of magic method __invoke()
     */
    public function __invoke()
    {
        return call_user_func_array($this->closure, func_get_args());
    }

    /**
     * Implementation of Serializable::serialize()
     *
     * @return  string  The serialized closure
     */
    public function serialize()
    {
        if ($this->scope === null) {
            $this->scope = new ClosureScope();
            $this->scope->toserialize++;
        }

        $this->scope->serializations++;

        $scope = $object = null;
        $reflector = $this->getReflector();

        if($reflector->isBindingRequired()){
            $object = $reflector->getClosureThis();
            static::wrapClosures($object, $this->scope);
            if($scope = $reflector->getClosureScopeClass()){
                $scope = $scope->name;
            }
        } else {
            if($scope = $reflector->getClosureScopeClass()){
                $scope = $scope->name;
            }
        }

        $this->reference = spl_object_hash($this->closure);

        $this->scope[$this->closure] = $this;

        $use = $this->transformUseVariables($reflector->getUseVariables());
        $code = $reflector->getCode();

        $this->mapByReference($use);

        $ret = \serialize(array(
            'use' => $use,
            'function' => $code,
            'scope' => $scope,
            'this' => $object,
            'self' => $this->reference,
        ));

        if (static::$securityProvider !== null) {
            $data = static::$securityProvider->sign($ret);
            $ret =  '@' . $data['hash'] . '.' . $data['closure'];
        }

        if (!--$this->scope->serializations && !--$this->scope->toserialize) {
            $this->scope = null;
        }

        return $ret;
    }

    /**
     * Transform the use variables before serialization.
     *
     * @param  array  $data The Closure's use variables
     * @return array
     */
    protected function transformUseVariables($data)
    {
        return $data;
    }

    /**
     * Implementation of Serializable::unserialize()
     *
     * @param   string $data Serialized data
     * @throws SecurityException
     */
    public function unserialize($data)
    {
        ClosureStream::register();

        if (static::$securityProvider !== null) {
            if ($data[0] !== '@') {
                throw new SecurityException("The serialized closure is not signed. ".
                    "Make sure you use a security provider for both serialization and unserialization.");
            }

            if ($data[1] !== '{') {
                $separator = strpos($data, '.');
                if ($separator === false) {
                    throw new SecurityException('Invalid signed closure');
                }
                $hash = substr($data, 1, $separator - 1);
                $closure = substr($data, $separator + 1);

                $data = ['hash' => $hash, 'closure' => $closure];

                unset($hash, $closure);
            } else {
                $data = json_decode(substr($data, 1), true);
            }

            if (!is_array($data) || !static::$securityProvider->verify($data)) {
                throw new SecurityException("Your serialized closure might have been modified and it's unsafe to be unserialized. " .
                    "Make sure you use the same security provider, with the same settings, " .
                    "both for serialization and unserialization.");
            }

            $data = $data['closure'];
        } elseif ($data[0] === '@') {
            if ($data[1] !== '{') {
                $separator = strpos($data, '.');
                if ($separator === false) {
                    throw new SecurityException('Invalid signed closure');
                }
                $hash = substr($data, 1, $separator - 1);
                $closure = substr($data, $separator + 1);

                $data = ['hash' => $hash, 'closure' => $closure];

                unset($hash, $closure);
            } else {
                $data = json_decode(substr($data, 1), true);
            }

            if (!is_array($data) || !isset($data['closure']) || !isset($data['hash'])) {
                throw new SecurityException('Invalid signed closure');
            }

            $data = $data['closure'];
        }

        $this->code = \unserialize($data);

        // unset data
        unset($data);

        $this->code['objects'] = array();

        if ($this->code['use']) {
            $this->scope = new ClosureScope();
            $this->code['use'] = $this->resolveUseVariables($this->code['use']);
            $this->mapPointers($this->code['use']);
            extract($this->code['use'], EXTR_OVERWRITE | EXTR_REFS);
            $this->scope = null;
        }

        $this->closure = include(ClosureStream::STREAM_PROTO . '://' . $this->code['function']);

        if($this->code['this'] === $this){
            $this->code['this'] = null;
        }

        $this->closure = $this->closure->bindTo($this->code['this'], $this->code['scope']);

        if(!empty($this->code['objects'])){
            foreach ($this->code['objects'] as $item){
                $item['property']->setValue($item['instance'], $item['object']->getClosure());
            }
        }

        $this->code = $this->code['function'];
    }

    /**
     * Resolve the use variables after unserialization.
     *
     * @param  array  $data The Closure's transformed use variables
     * @return array
     */
    protected function resolveUseVariables($data)
    {
        return $data;
    }

    /**
     * Wraps a closure and sets the serialization context (if any)
     *
     * @param   Closure $closure Closure to be wrapped
     *
     * @return  self    The wrapped closure
     */
    public static function from(Closure $closure)
    {
        if (static::$context === null) {
            $instance = new static($closure);
        } elseif (isset(static::$context->scope[$closure])) {
            $instance = static::$context->scope[$closure];
        } else {
            $instance = new static($closure);
            static::$context->scope[$closure] = $instance;
        }

        return $instance;
    }

    /**
     * Increments the context lock counter or creates a new context if none exist
     */
    public static function enterContext()
    {
        if (static::$context === null) {
            static::$context = new ClosureContext();
        }

        static::$context->locks++;
    }

    /**
     * Decrements the context lock counter and destroy the context when it reaches to 0
     */
    public static function exitContext()
    {
        if (static::$context !== null && !--static::$context->locks) {
            static::$context = null;
        }
    }

    /**
     * @param string $secret
     */
    public static function setSecretKey($secret)
    {
        if(static::$securityProvider === null){
            static::$securityProvider = new SecurityProvider($secret);
        }
    }

    /**
     * @param ISecurityProvider $securityProvider
     */
    public static function addSecurityProvider(ISecurityProvider $securityProvider)
    {
        static::$securityProvider = $securityProvider;
    }

    /**
     * Remove security provider
     */
    public static function removeSecurityProvider()
    {
        static::$securityProvider = null;
    }

    /**
     * @return null|ISecurityProvider
     */
    public static function getSecurityProvider()
    {
        return static::$securityProvider;
    }

    /**
     * Wrap closures
     *
     * @internal
     * @param $data
     * @param ClosureScope|SplObjectStorage|null $storage
     */
    public static function wrapClosures(&$data, SplObjectStorage $storage = null)
    {
        if($storage === null){
            $storage = static::$context->scope;
        }

        if($data instanceof Closure){
            $data = static::from($data);
        } elseif (is_array($data)){
            if(isset($data[self::ARRAY_RECURSIVE_KEY])){
                return;
            }
            $data[self::ARRAY_RECURSIVE_KEY] = true;
            foreach ($data as $key => &$value){
                if($key === self::ARRAY_RECURSIVE_KEY){
                    continue;
                }
                static::wrapClosures($value, $storage);
            }
            unset($value);
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        } elseif($data instanceof \stdClass){
            if(isset($storage[$data])){
                $data = $storage[$data];
                return;
            }
            $data = $storage[$data] = clone($data);
            foreach ($data as &$value){
                static::wrapClosures($value, $storage);
            }
            unset($value);
        } elseif (is_object($data) && ! $data instanceof static){
            if(isset($storage[$data])){
                $data = $storage[$data];
                return;
            }
            $instance = $data;
            $reflection = new ReflectionObject($instance);
            if(!$reflection->isUserDefined()){
                $storage[$instance] = $data;
                return;
            }
            $storage[$instance] = $data = $reflection->newInstanceWithoutConstructor();

            do{
                if(!$reflection->isUserDefined()){
                    break;
                }
                foreach ($reflection->getProperties() as $property){
                    if($property->isStatic() || !$property->getDeclaringClass()->isUserDefined()){
                        continue;
                    }
                    $property->setAccessible(true);
                    if (PHP_VERSION >= 7.4 && !$property->isInitialized($instance)) {
                        continue;
                    }
                    $value = $property->getValue($instance);
                    if(is_array($value) || is_object($value)){
                        static::wrapClosures($value, $storage);
                    }
                    $property->setValue($data, $value);
                };
            } while($reflection = $reflection->getParentClass());
        }
    }

    /**
     * Unwrap closures
     *
     * @internal
     * @param $data
     * @param SplObjectStorage|null $storage
     */
    public static function unwrapClosures(&$data, SplObjectStorage $storage = null)
    {
        if($storage === null){
            $storage = static::$context->scope;
        }

        if($data instanceof static){
            $data = $data->getClosure();
        } elseif (is_array($data)){
            if(isset($data[self::ARRAY_RECURSIVE_KEY])){
                return;
            }
            $data[self::ARRAY_RECURSIVE_KEY] = true;
            foreach ($data as $key => &$value){
                if($key === self::ARRAY_RECURSIVE_KEY){
                    continue;
                }
                static::unwrapClosures($value, $storage);
            }
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        }elseif ($data instanceof \stdClass){
            if(isset($storage[$data])){
                return;
            }
            $storage[$data] = true;
            foreach ($data as &$property){
                static::unwrapClosures($property, $storage);
            }
        } elseif (is_object($data) && !($data instanceof Closure)){
            if(isset($storage[$data])){
                return;
            }
            $storage[$data] = true;
            $reflection = new ReflectionObject($data);

            do{
                if(!$reflection->isUserDefined()){
                    break;
                }
                foreach ($reflection->getProperties() as $property){
                    if($property->isStatic() || !$property->getDeclaringClass()->isUserDefined()){
                        continue;
                    }
                    $property->setAccessible(true);
                    if (PHP_VERSION >= 7.4 && !$property->isInitialized($data)) {
                        continue;
                    }
                    $value = $property->getValue($data);
                    if(is_array($value) || is_object($value)){
                        static::unwrapClosures($value, $storage);
                        $property->setValue($data, $value);
                    }
                };
            } while($reflection = $reflection->getParentClass());
        }
    }

    /**
     * Creates a new closure from arbitrary code,
     * emulating create_function, but without using eval
     *
     * @param string$args
     * @param string $code
     * @return Closure
     */
    public static function createClosure($args, $code)
    {
        ClosureStream::register();
        return include(ClosureStream::STREAM_PROTO . '://function(' . $args. '){' . $code . '};');
    }

    /**
     * Internal method used to map closure pointers
     * @internal
     * @param $data
     */
    protected function mapPointers(&$data)
    {
        $scope = $this->scope;

        if ($data instanceof static) {
            $data = &$data->closure;
        } elseif (is_array($data)) {
            if(isset($data[self::ARRAY_RECURSIVE_KEY])){
                return;
            }
            $data[self::ARRAY_RECURSIVE_KEY] = true;
            foreach ($data as $key => &$value){
                if($key === self::ARRAY_RECURSIVE_KEY){
                    continue;
                } elseif ($value instanceof static) {
                    $data[$key] = &$value->closure;
                } elseif ($value instanceof SelfReference && $value->hash === $this->code['self']){
                    $data[$key] = &$this->closure;
                } else {
                    $this->mapPointers($value);
                }
            }
            unset($value);
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        } elseif ($data instanceof \stdClass) {
            if(isset($scope[$data])){
                return;
            }
            $scope[$data] = true;
            foreach ($data as $key => &$value){
                if ($value instanceof SelfReference && $value->hash === $this->code['self']){
                    $data->{$key} = &$this->closure;
                } elseif(is_array($value) || is_object($value)) {
                    $this->mapPointers($value);
                }
            }
            unset($value);
        } elseif (is_object($data) && !($data instanceof Closure)){
            if(isset($scope[$data])){
                return;
            }
            $scope[$data] = true;
            $reflection = new ReflectionObject($data);
            do{
                if(!$reflection->isUserDefined()){
                    break;
                }
                foreach ($reflection->getProperties() as $property){
                    if($property->isStatic() || !$property->getDeclaringClass()->isUserDefined()){
                        continue;
                    }
                    $property->setAccessible(true);
                    if (PHP_VERSION >= 7.4 && !$property->isInitialized($data)) {
                        continue;
                    }
                    $item = $property->getValue($data);
                    if ($item instanceof SerializableClosure || ($item instanceof SelfReference && $item->hash === $this->code['self'])) {
                        $this->code['objects'][] = array(
                            'instance' => $data,
                            'property' => $property,
                            'object' => $item instanceof SelfReference ? $this : $item,
                        );
                    } elseif (is_array($item) || is_object($item)) {
                        $this->mapPointers($item);
                        $property->setValue($data, $item);
                    }
                }
            } while($reflection = $reflection->getParentClass());
        }
    }

    /**
     * Internal method used to map closures by reference
     *
     * @internal
     * @param   mixed &$data
     */
    protected function mapByReference(&$data)
    {
        if ($data instanceof Closure) {
            if($data === $this->closure){
                $data = new SelfReference($this->reference);
                return;
            }

            if (isset($this->scope[$data])) {
                $data = $this->scope[$data];
                return;
            }

            $instance = new static($data);

            if (static::$context !== null) {
                static::$context->scope->toserialize--;
            } else {
                $instance->scope = $this->scope;
            }

            $data = $this->scope[$data] = $instance;
        } elseif (is_array($data)) {
            if(isset($data[self::ARRAY_RECURSIVE_KEY])){
                return;
            }
            $data[self::ARRAY_RECURSIVE_KEY] = true;
            foreach ($data as $key => &$value){
                if($key === self::ARRAY_RECURSIVE_KEY){
                    continue;
                }
                $this->mapByReference($value);
            }
            unset($value);
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        } elseif ($data instanceof \stdClass) {
            if(isset($this->scope[$data])){
                $data = $this->scope[$data];
                return;
            }
            $instance = $data;
            $this->scope[$instance] = $data = clone($data);

            foreach ($data as &$value){
                $this->mapByReference($value);
            }
            unset($value);
        } elseif (is_object($data) && !$data instanceof SerializableClosure){
            if(isset($this->scope[$data])){
                $data = $this->scope[$data];
                return;
            }

            $instance = $data;
            $reflection = new ReflectionObject($data);
            if(!$reflection->isUserDefined()){
                $this->scope[$instance] = $data;
                return;
            }
            $this->scope[$instance] = $data = $reflection->newInstanceWithoutConstructor();

            do{
                if(!$reflection->isUserDefined()){
                    break;
                }
                foreach ($reflection->getProperties() as $property){
                    if($property->isStatic() || !$property->getDeclaringClass()->isUserDefined()){
                        continue;
                    }
                    $property->setAccessible(true);
                    if (PHP_VERSION >= 7.4 && !$property->isInitialized($instance)) {
                        continue;
                    }
                    $value = $property->getValue($instance);
                    if(is_array($value) || is_object($value)){
                        $this->mapByReference($value);
                    }
                    $property->setValue($data, $value);
                }
            } while($reflection = $reflection->getParentClass());
        }
    }

}
