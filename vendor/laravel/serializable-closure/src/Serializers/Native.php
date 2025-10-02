<?php

namespace Laravel\SerializableClosure\Serializers;

use Closure;
use DateTimeInterface;
use Laravel\SerializableClosure\Contracts\Serializable;
use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Support\ClosureScope;
use Laravel\SerializableClosure\Support\ClosureStream;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use Laravel\SerializableClosure\Support\SelfReference;
use Laravel\SerializableClosure\UnsignedSerializableClosure;
use ReflectionObject;
use ReflectionProperty;
use UnitEnum;

class Native implements Serializable
{
    /**
     * Transform the use variables before serialization.
     *
     * @var \Closure|null
     */
    public static $transformUseVariables;

    /**
     * Resolve the use variables after unserialization.
     *
     * @var \Closure|null
     */
    public static $resolveUseVariables;

    /**
     * The closure to be serialized/unserialized.
     *
     * @var \Closure
     */
    protected $closure;

    /**
     * The closure's reflection.
     *
     * @var \Laravel\SerializableClosure\Support\ReflectionClosure|null
     */
    protected $reflector;

    /**
     * The closure's code.
     *
     * @var array|null
     */
    protected $code;

    /**
     * The closure's reference.
     *
     * @var string
     */
    protected $reference;

    /**
     * The closure's scope.
     *
     * @var \Laravel\SerializableClosure\Support\ClosureScope|null
     */
    protected $scope;

    /**
     * The "key" that marks an array as recursive.
     */
    const ARRAY_RECURSIVE_KEY = 'LARAVEL_SERIALIZABLE_RECURSIVE_KEY';

    /**
     * Creates a new serializable closure instance.
     *
     * @param  \Closure  $closure
     * @return void
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Resolve the closure with the given arguments.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->closure, func_get_args());
    }

    /**
     * Gets the closure.
     *
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Get the serializable representation of the closure.
     *
     * @return array
     */
    public function __serialize()
    {
        if ($this->scope === null) {
            $this->scope = new ClosureScope();
            $this->scope->toSerialize++;
        }

        $this->scope->serializations++;

        $scope = $object = null;
        $reflector = $this->getReflector();

        if ($reflector->isBindingRequired()) {
            $object = $reflector->getClosureThis();

            static::wrapClosures($object, $this->scope);
        }

        if ($scope = $reflector->getClosureScopeClass()) {
            $scope = $scope->name;
        }

        $this->reference = spl_object_hash($this->closure);

        $this->scope[$this->closure] = $this;

        $use = $reflector->getUseVariables();

        if (static::$transformUseVariables) {
            $use = call_user_func(static::$transformUseVariables, $reflector->getUseVariables());
        }

        $code = $reflector->getCode();

        $this->mapByReference($use);

        $data = [
            'use' => $use,
            'function' => $code,
            'scope' => $scope,
            'this' => $object,
            'self' => $this->reference,
        ];

        if (! --$this->scope->serializations && ! --$this->scope->toSerialize) {
            $this->scope = null;
        }

        return $data;
    }

    /**
     * Restore the closure after serialization.
     *
     * @param  array  $data
     * @return void
     */
    public function __unserialize($data)
    {
        ClosureStream::register();

        $this->code = $data;
        unset($data);

        $this->code['objects'] = [];

        if ($this->code['use']) {
            $this->scope = new ClosureScope();

            if (static::$resolveUseVariables) {
                $this->code['use'] = call_user_func(static::$resolveUseVariables, $this->code['use']);
            }

            $this->mapPointers($this->code['use']);

            extract($this->code['use'], EXTR_OVERWRITE | EXTR_REFS);

            $this->scope = null;
        }

        $this->closure = include ClosureStream::STREAM_PROTO.'://'.$this->code['function'];

        if ($this->code['this'] === $this) {
            $this->code['this'] = null;
        }

        $this->closure = $this->closure->bindTo($this->code['this'], $this->code['scope']);

        if (! empty($this->code['objects'])) {
            foreach ($this->code['objects'] as $item) {
                $item['property']->setValue($item['instance'], $item['object']->getClosure());
            }
        }

        $this->code = $this->code['function'];
    }

    /**
     * Ensures the given closures are serializable.
     *
     * @param  mixed  $data
     * @param  \Laravel\SerializableClosure\Support\ClosureScope  $storage
     * @return void
     */
    public static function wrapClosures(&$data, $storage)
    {
        if ($data instanceof Closure) {
            $data = new static($data);
        } elseif (is_array($data)) {
            if (isset($data[self::ARRAY_RECURSIVE_KEY])) {
                return;
            }

            $data[self::ARRAY_RECURSIVE_KEY] = true;

            foreach ($data as $key => &$value) {
                if ($key === self::ARRAY_RECURSIVE_KEY) {
                    continue;
                }
                static::wrapClosures($value, $storage);
            }

            unset($value);
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        } elseif ($data instanceof \stdClass) {
            if (isset($storage[$data])) {
                $data = $storage[$data];

                return;
            }

            $data = $storage[$data] = clone $data;

            foreach ($data as &$value) {
                static::wrapClosures($value, $storage);
            }

            unset($value);
        } elseif (is_object($data) && ! $data instanceof static && ! $data instanceof UnitEnum) {
            if (isset($storage[$data])) {
                $data = $storage[$data];

                return;
            }

            $instance = $data;
            $reflection = new ReflectionObject($instance);

            if (! $reflection->isUserDefined()) {
                $storage[$instance] = $data;

                return;
            }

            $storage[$instance] = $data = $reflection->newInstanceWithoutConstructor();

            do {
                if (! $reflection->isUserDefined()) {
                    break;
                }

                foreach ($reflection->getProperties() as $property) {
                    if ($property->isStatic() || ! $property->getDeclaringClass()->isUserDefined()) {
                        continue;
                    }

                    $property->setAccessible(true);

                    if (! $property->isInitialized($instance)) {
                        continue;
                    }

                    $value = $property->getValue($instance);

                    if (is_array($value) || is_object($value)) {
                        static::wrapClosures($value, $storage);
                    }

                    $property->setValue($data, $value);
                }
            } while ($reflection = $reflection->getParentClass());
        }
    }

    /**
     * Gets the closure's reflector.
     *
     * @return \Laravel\SerializableClosure\Support\ReflectionClosure
     */
    public function getReflector()
    {
        if ($this->reflector === null) {
            $this->code = null;
            $this->reflector = new ReflectionClosure($this->closure);
        }

        return $this->reflector;
    }

    /**
     * Internal method used to map closure pointers.
     *
     * @param  mixed  $data
     * @return void
     */
    protected function mapPointers(&$data)
    {
        $scope = $this->scope;

        if ($data instanceof static) {
            $data = &$data->closure;
        } elseif (is_array($data)) {
            if (isset($data[self::ARRAY_RECURSIVE_KEY])) {
                return;
            }

            $data[self::ARRAY_RECURSIVE_KEY] = true;

            foreach ($data as $key => &$value) {
                if ($key === self::ARRAY_RECURSIVE_KEY) {
                    continue;
                } elseif ($value instanceof static) {
                    $data[$key] = &$value->closure;
                } elseif ($value instanceof SelfReference && $value->hash === $this->code['self']) {
                    $data[$key] = &$this->closure;
                } else {
                    $this->mapPointers($value);
                }
            }

            unset($value);
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        } elseif ($data instanceof \stdClass) {
            if (isset($scope[$data])) {
                return;
            }

            $scope[$data] = true;

            foreach ($data as $key => &$value) {
                if ($value instanceof SelfReference && $value->hash === $this->code['self']) {
                    $data->{$key} = &$this->closure;
                } elseif (is_array($value) || is_object($value)) {
                    $this->mapPointers($value);
                }
            }

            unset($value);
        } elseif (is_object($data) && ! ($data instanceof Closure)) {
            if (isset($scope[$data])) {
                return;
            }

            $scope[$data] = true;
            $reflection = new ReflectionObject($data);

            do {
                if (! $reflection->isUserDefined()) {
                    break;
                }

                foreach ($reflection->getProperties() as $property) {
                    if ($property->isStatic() || ! $property->getDeclaringClass()->isUserDefined()) {
                        continue;
                    }

                    $property->setAccessible(true);

                    if (! $property->isInitialized($data) || $property->isReadOnly()) {
                        continue;
                    }

                    $item = $property->getValue($data);

                    if ($item instanceof SerializableClosure || $item instanceof UnsignedSerializableClosure || ($item instanceof SelfReference && $item->hash === $this->code['self'])) {
                        $this->code['objects'][] = [
                            'instance' => $data,
                            'property' => $property,
                            'object' => $item instanceof SelfReference ? $this : $item,
                        ];
                    } elseif (is_array($item) || is_object($item)) {
                        $this->mapPointers($item);
                        $property->setValue($data, $item);
                    }
                }
            } while ($reflection = $reflection->getParentClass());
        }
    }

    /**
     * Internal method used to map closures by reference.
     *
     * @param  mixed  $data
     * @return void
     */
    protected function mapByReference(&$data)
    {
        if ($data instanceof Closure) {
            if ($data === $this->closure) {
                $data = new SelfReference($this->reference);

                return;
            }

            if (isset($this->scope[$data])) {
                $data = $this->scope[$data];

                return;
            }

            $instance = new static($data);

            $instance->scope = $this->scope;

            $data = $this->scope[$data] = $instance;
        } elseif (is_array($data)) {
            if (isset($data[self::ARRAY_RECURSIVE_KEY])) {
                return;
            }

            $data[self::ARRAY_RECURSIVE_KEY] = true;

            foreach ($data as $key => &$value) {
                if ($key === self::ARRAY_RECURSIVE_KEY) {
                    continue;
                }

                $this->mapByReference($value);
            }

            unset($value);
            unset($data[self::ARRAY_RECURSIVE_KEY]);
        } elseif ($data instanceof \stdClass) {
            if (isset($this->scope[$data])) {
                $data = $this->scope[$data];

                return;
            }

            $instance = $data;
            $this->scope[$instance] = $data = clone $data;

            foreach ($data as &$value) {
                $this->mapByReference($value);
            }

            unset($value);
        } elseif (is_object($data) && ! $data instanceof SerializableClosure && ! $data instanceof UnsignedSerializableClosure) {
            if (isset($this->scope[$data])) {
                $data = $this->scope[$data];

                return;
            }

            $instance = $data;

            if ($data instanceof DateTimeInterface) {
                $this->scope[$instance] = $data;

                return;
            }

            if ($data instanceof UnitEnum) {
                $this->scope[$instance] = $data;

                return;
            }

            $reflection = new ReflectionObject($data);

            if (! $reflection->isUserDefined()) {
                $this->scope[$instance] = $data;

                return;
            }

            $this->scope[$instance] = $data = $reflection->newInstanceWithoutConstructor();

            do {
                if (! $reflection->isUserDefined()) {
                    break;
                }

                foreach ($reflection->getProperties() as $property) {
                    if ($property->isStatic() || ! $property->getDeclaringClass()->isUserDefined() || $this->isVirtualProperty($property)) {
                        continue;
                    }

                    $property->setAccessible(true);

                    if (! $property->isInitialized($instance) || ($property->isReadOnly() && $property->class !== $reflection->name)) {
                        continue;
                    }

                    $value = $property->getValue($instance);

                    if (is_array($value) || is_object($value)) {
                        $this->mapByReference($value);
                    }

                    $property->setValue($data, $value);
                }
            } while ($reflection = $reflection->getParentClass());
        }
    }

    /**
     * Determine is virtual property.
     *
     * @param  \ReflectionProperty  $property
     * @return bool
     */
    protected function isVirtualProperty(ReflectionProperty $property): bool
    {
        return method_exists($property, 'isVirtual') && $property->isVirtual();
    }
}
