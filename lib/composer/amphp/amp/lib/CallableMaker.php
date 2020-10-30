<?php

namespace Amp;

// @codeCoverageIgnoreStart
if (\PHP_VERSION_ID < 70100) {
    /** @psalm-suppress DuplicateClass */
    trait CallableMaker
    {
        /** @var \ReflectionClass */
        private static $__reflectionClass;

        /** @var \ReflectionMethod[] */
        private static $__reflectionMethods = [];

        /**
         * Creates a callable from a protected or private instance method that may be invoked by callers requiring a
         * publicly invokable callback.
         *
         * @param string $method Instance method name.
         *
         * @return callable
         *
         * @psalm-suppress MixedInferredReturnType
         */
        private function callableFromInstanceMethod(string $method): callable
        {
            if (!isset(self::$__reflectionMethods[$method])) {
                if (self::$__reflectionClass === null) {
                    self::$__reflectionClass = new \ReflectionClass(self::class);
                }
                self::$__reflectionMethods[$method] = self::$__reflectionClass->getMethod($method);
            }

            return self::$__reflectionMethods[$method]->getClosure($this);
        }

        /**
         * Creates a callable from a protected or private static method that may be invoked by methods requiring a
         * publicly invokable callback.
         *
         * @param string $method Static method name.
         *
         * @return callable
         *
         * @psalm-suppress MixedInferredReturnType
         */
        private static function callableFromStaticMethod(string $method): callable
        {
            if (!isset(self::$__reflectionMethods[$method])) {
                if (self::$__reflectionClass === null) {
                    self::$__reflectionClass = new \ReflectionClass(self::class);
                }
                self::$__reflectionMethods[$method] = self::$__reflectionClass->getMethod($method);
            }

            return self::$__reflectionMethods[$method]->getClosure();
        }
    }
} else {
    /** @psalm-suppress DuplicateClass */
    trait CallableMaker
    {
        /**
         * @deprecated Use \Closure::fromCallable() instead of this method in PHP 7.1.
         */
        private function callableFromInstanceMethod(string $method): callable
        {
            return \Closure::fromCallable([$this, $method]);
        }

        /**
         * @deprecated Use \Closure::fromCallable() instead of this method in PHP 7.1.
         */
        private static function callableFromStaticMethod(string $method): callable
        {
            return \Closure::fromCallable([self::class, $method]);
        }
    }
} // @codeCoverageIgnoreEnd
