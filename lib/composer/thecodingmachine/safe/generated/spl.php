<?php

namespace Safe;

use Safe\Exceptions\SplException;

/**
 * This function returns an array with the names of the interfaces that the
 * given class and its parents implement.
 *
 * @param mixed $class An object (class instance) or a string (class or interface name).
 * @param bool $autoload Whether to allow this function to load the class automatically through
 * the __autoload magic method.
 * @return array An array on success.
 * @throws SplException
 *
 */
function class_implements($class, bool $autoload = true): array
{
    error_clear_last();
    $result = \class_implements($class, $autoload);
    if ($result === false) {
        throw SplException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns an array with the name of the parent classes of
 * the given class.
 *
 * @param mixed $class An object (class instance) or a string (class name).
 * @param bool $autoload Whether to allow this function to load the class automatically through
 * the __autoload magic method.
 * @return array An array on success.
 * @throws SplException
 *
 */
function class_parents($class, bool $autoload = true): array
{
    error_clear_last();
    $result = \class_parents($class, $autoload);
    if ($result === false) {
        throw SplException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns an array with the names of the traits that the
 * given class uses. This does however not include
 * any traits used by a parent class.
 *
 * @param mixed $class An object (class instance) or a string (class name).
 * @param bool $autoload Whether to allow this function to load the class automatically through
 * the __autoload magic method.
 * @return array An array on success.
 * @throws SplException
 *
 */
function class_uses($class, bool $autoload = true): array
{
    error_clear_last();
    $result = \class_uses($class, $autoload);
    if ($result === false) {
        throw SplException::createFromPhpError();
    }
    return $result;
}


/**
 * Register a function with the spl provided __autoload queue. If the queue
 * is not yet activated it will be activated.
 *
 * If your code has an existing __autoload function then
 * this function must be explicitly registered on the __autoload queue. This
 * is because spl_autoload_register will effectively
 * replace the engine cache for the __autoload function
 * by either spl_autoload or
 * spl_autoload_call.
 *
 * If there must be multiple autoload functions, spl_autoload_register
 * allows for this. It effectively creates a queue of autoload functions, and
 * runs through each of them in the order they are defined. By contrast,
 * __autoload may only be defined once.
 *
 * @param callable(string):void $autoload_function The autoload function being registered.
 * If no parameter is provided, then the default implementation of
 * spl_autoload will be registered.
 * @param bool $throw This parameter specifies whether
 * spl_autoload_register should throw
 * exceptions when the autoload_function
 * cannot be registered.
 * @param bool $prepend If true, spl_autoload_register will prepend
 * the autoloader on the autoload queue instead of appending it.
 * @throws SplException
 *
 */
function spl_autoload_register(callable $autoload_function = null, bool $throw = true, bool $prepend = false): void
{
    error_clear_last();
    if ($prepend !== false) {
        $result = \spl_autoload_register($autoload_function, $throw, $prepend);
    } elseif ($throw !== true) {
        $result = \spl_autoload_register($autoload_function, $throw);
    } elseif ($autoload_function !== null) {
        $result = \spl_autoload_register($autoload_function);
    } else {
        $result = \spl_autoload_register();
    }
    if ($result === false) {
        throw SplException::createFromPhpError();
    }
}


/**
 * Removes a function from the autoload queue. If the queue
 * is activated and empty after removing the given function then it will
 * be deactivated.
 *
 * When this function results in the queue being deactivated, any
 * __autoload function that previously existed will not be reactivated.
 *
 * @param mixed $autoload_function The autoload function being unregistered.
 * @throws SplException
 *
 */
function spl_autoload_unregister($autoload_function): void
{
    error_clear_last();
    $result = \spl_autoload_unregister($autoload_function);
    if ($result === false) {
        throw SplException::createFromPhpError();
    }
}
