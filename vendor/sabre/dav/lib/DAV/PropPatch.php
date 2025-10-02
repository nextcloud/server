<?php

declare(strict_types=1);

namespace Sabre\DAV;

use UnexpectedValueException;

/**
 * This class represents a set of properties that are going to be updated.
 *
 * Usually this is simply a PROPPATCH request, but it can also be used for
 * internal updates.
 *
 * Property updates must always be atomic. This means that a property update
 * must either completely succeed, or completely fail.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PropPatch
{
    /**
     * Properties that are being updated.
     *
     * This is a key-value list. If the value is null, the property is supposed
     * to be deleted.
     *
     * @var array
     */
    protected $mutations;

    /**
     * A list of properties and the result of the update. The result is in the
     * form of a HTTP status code.
     *
     * @var array
     */
    protected $result = [];

    /**
     * This is the list of callbacks when we're performing the actual update.
     *
     * @var array
     */
    protected $propertyUpdateCallbacks = [];

    /**
     * This property will be set to true if the operation failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * Constructor.
     *
     * @param array $mutations A list of updates
     */
    public function __construct(array $mutations)
    {
        $this->mutations = $mutations;
    }

    /**
     * Call this function if you wish to handle updating certain properties.
     * For instance, your class may be responsible for handling updates for the
     * {DAV:}displayname property.
     *
     * In that case, call this method with the first argument
     * "{DAV:}displayname" and a second argument that's a method that does the
     * actual updating.
     *
     * It's possible to specify more than one property as an array.
     *
     * The callback must return a boolean or an it. If the result is true, the
     * operation was considered successful. If it's false, it's consided
     * failed.
     *
     * If the result is an integer, we'll use that integer as the http status
     * code associated with the operation.
     *
     * @param string|string[] $properties
     */
    public function handle($properties, callable $callback)
    {
        $usedProperties = [];
        foreach ((array) $properties as $propertyName) {
            if (array_key_exists($propertyName, $this->mutations) && !isset($this->result[$propertyName])) {
                $usedProperties[] = $propertyName;
                // HTTP Accepted
                $this->result[$propertyName] = 202;
            }
        }

        // Only registering if there's any unhandled properties.
        if (!$usedProperties) {
            return;
        }
        $this->propertyUpdateCallbacks[] = [
            // If the original argument to this method was a string, we need
            // to also make sure that it stays that way, so the commit function
            // knows how to format the arguments to the callback.
            is_string($properties) ? $properties : $usedProperties,
            $callback,
        ];
    }

    /**
     * Call this function if you wish to handle _all_ properties that haven't
     * been handled by anything else yet. Note that you effectively claim with
     * this that you promise to process _all_ properties that are coming in.
     */
    public function handleRemaining(callable $callback)
    {
        $properties = $this->getRemainingMutations();
        if (!$properties) {
            // Nothing to do, don't register callback
            return;
        }

        foreach ($properties as $propertyName) {
            // HTTP Accepted
            $this->result[$propertyName] = 202;

            $this->propertyUpdateCallbacks[] = [
                $properties,
                $callback,
            ];
        }
    }

    /**
     * Sets the result code for one or more properties.
     *
     * @param string|string[] $properties
     * @param int             $resultCode
     */
    public function setResultCode($properties, $resultCode)
    {
        foreach ((array) $properties as $propertyName) {
            $this->result[$propertyName] = $resultCode;
        }

        if ($resultCode >= 400) {
            $this->failed = true;
        }
    }

    /**
     * Sets the result code for all properties that did not have a result yet.
     *
     * @param int $resultCode
     */
    public function setRemainingResultCode($resultCode)
    {
        $this->setResultCode(
            $this->getRemainingMutations(),
            $resultCode
        );
    }

    /**
     * Returns the list of properties that don't have a result code yet.
     *
     * This method returns a list of property names, but not its values.
     *
     * @return string[]
     */
    public function getRemainingMutations()
    {
        $remaining = [];
        foreach ($this->mutations as $propertyName => $propValue) {
            if (!isset($this->result[$propertyName])) {
                $remaining[] = $propertyName;
            }
        }

        return $remaining;
    }

    /**
     * Returns the list of properties that don't have a result code yet.
     *
     * This method returns list of properties and their values.
     *
     * @return array
     */
    public function getRemainingValues()
    {
        $remaining = [];
        foreach ($this->mutations as $propertyName => $propValue) {
            if (!isset($this->result[$propertyName])) {
                $remaining[$propertyName] = $propValue;
            }
        }

        return $remaining;
    }

    /**
     * Performs the actual update, and calls all callbacks.
     *
     * This method returns true or false depending on if the operation was
     * successful.
     *
     * @return bool
     */
    public function commit()
    {
        // First we validate if every property has a handler
        foreach ($this->mutations as $propertyName => $value) {
            if (!isset($this->result[$propertyName])) {
                $this->failed = true;
                $this->result[$propertyName] = 403;
            }
        }

        foreach ($this->propertyUpdateCallbacks as $callbackInfo) {
            if ($this->failed) {
                break;
            }
            if (is_string($callbackInfo[0])) {
                $this->doCallbackSingleProp($callbackInfo[0], $callbackInfo[1]);
            } else {
                $this->doCallbackMultiProp($callbackInfo[0], $callbackInfo[1]);
            }
        }

        /*
         * If anywhere in this operation updating a property failed, we must
         * update all other properties accordingly.
         */
        if ($this->failed) {
            foreach ($this->result as $propertyName => $status) {
                if (202 === $status) {
                    // Failed dependency
                    $this->result[$propertyName] = 424;
                }
            }
        }

        return !$this->failed;
    }

    /**
     * Executes a property callback with the single-property syntax.
     *
     * @param string $propertyName
     */
    private function doCallBackSingleProp($propertyName, callable $callback)
    {
        $result = $callback($this->mutations[$propertyName]);
        if (is_bool($result)) {
            if ($result) {
                if (is_null($this->mutations[$propertyName])) {
                    // Delete
                    $result = 204;
                } else {
                    // Update
                    $result = 200;
                }
            } else {
                // Fail
                $result = 403;
            }
        }
        if (!is_int($result)) {
            throw new UnexpectedValueException('A callback sent to handle() did not return an int or a bool');
        }
        $this->result[$propertyName] = $result;
        if ($result >= 400) {
            $this->failed = true;
        }
    }

    /**
     * Executes a property callback with the multi-property syntax.
     */
    private function doCallBackMultiProp(array $propertyList, callable $callback)
    {
        $argument = [];
        foreach ($propertyList as $propertyName) {
            $argument[$propertyName] = $this->mutations[$propertyName];
        }

        $result = $callback($argument);

        if (is_array($result)) {
            foreach ($propertyList as $propertyName) {
                if (!isset($result[$propertyName])) {
                    $resultCode = 500;
                } else {
                    $resultCode = $result[$propertyName];
                }
                if ($resultCode >= 400) {
                    $this->failed = true;
                }
                $this->result[$propertyName] = $resultCode;
            }
        } elseif (true === $result) {
            // Success
            foreach ($argument as $propertyName => $propertyValue) {
                $this->result[$propertyName] = is_null($propertyValue) ? 204 : 200;
            }
        } elseif (false === $result) {
            // Fail :(
            $this->failed = true;
            foreach ($propertyList as $propertyName) {
                $this->result[$propertyName] = 403;
            }
        } else {
            throw new UnexpectedValueException('A callback sent to handle() did not return an array or a bool');
        }
    }

    /**
     * Returns the result of the operation.
     *
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns the full list of mutations.
     *
     * @return array
     */
    public function getMutations()
    {
        return $this->mutations;
    }
}
