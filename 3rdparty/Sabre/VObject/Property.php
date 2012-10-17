<?php

namespace Sabre\VObject;

/**
 * VObject Property
 *
 * A property in VObject is usually in the form PARAMNAME:paramValue.
 * An example is : SUMMARY:Weekly meeting
 *
 * Properties can also have parameters:
 * SUMMARY;LANG=en:Weekly meeting.
 *
 * Parameters can be accessed using the ArrayAccess interface.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Property extends Node {

    /**
     * Propertyname
     *
     * @var string
     */
    public $name;

    /**
     * Group name
     *
     * This may be something like 'HOME' for vcards.
     *
     * @var string
     */
    public $group;

    /**
     * Property parameters
     *
     * @var array
     */
    public $parameters = array();

    /**
     * Property value
     *
     * @var string
     */
    public $value;

    /**
     * If properties are added to this map, they will be automatically mapped
     * to their respective classes, if parsed by the reader or constructed with
     * the 'create' method.
     *
     * @var array
     */
    static public $classMap = array(
        'COMPLETED'     => 'Sabre\\VObject\\Property\\DateTime',
        'CREATED'       => 'Sabre\\VObject\\Property\\DateTime',
        'DTEND'         => 'Sabre\\VObject\\Property\\DateTime',
        'DTSTAMP'       => 'Sabre\\VObject\\Property\\DateTime',
        'DTSTART'       => 'Sabre\\VObject\\Property\\DateTime',
        'DUE'           => 'Sabre\\VObject\\Property\\DateTime',
        'EXDATE'        => 'Sabre\\VObject\\Property\\MultiDateTime',
        'LAST-MODIFIED' => 'Sabre\\VObject\\Property\\DateTime',
        'RECURRENCE-ID' => 'Sabre\\VObject\\Property\\DateTime',
        'TRIGGER'       => 'Sabre\\VObject\\Property\\DateTime',
        'N'             => 'Sabre\\VObject\\Property\\Compound',
        'ORG'           => 'Sabre\\VObject\\Property\\Compound',
        'ADR'           => 'Sabre\\VObject\\Property\\Compound',
        'CATEGORIES'    => 'Sabre\\VObject\\Property\\Compound',
    );

    /**
     * Creates the new property by name, but in addition will also see if
     * there's a class mapped to the property name.
     *
     * Parameters can be specified with the optional third argument. Parameters
     * must be a key->value map of the parameter name, and value. If the value
     * is specified as an array, it is assumed that multiple parameters with
     * the same name should be added.
     *
     * @param string $name
     * @param string $value
     * @param array $parameters
     * @return Property
     */
    static public function create($name, $value = null, array $parameters = array()) {

        $name = strtoupper($name);
        $shortName = $name;
        $group = null;
        if (strpos($shortName,'.')!==false) {
            list($group, $shortName) = explode('.', $shortName);
        }

        if (isset(self::$classMap[$shortName])) {
            return new self::$classMap[$shortName]($name, $value, $parameters);
        } else {
            return new self($name, $value, $parameters);
        }

    }

    /**
     * Creates a new property object
     *
     * Parameters can be specified with the optional third argument. Parameters
     * must be a key->value map of the parameter name, and value. If the value
     * is specified as an array, it is assumed that multiple parameters with
     * the same name should be added.
     *
     * @param string $name
     * @param string $value
     * @param array $parameters
     */
    public function __construct($name, $value = null, array $parameters = array()) {

        if (!is_scalar($value) && !is_null($value)) {
            throw new \InvalidArgumentException('The value argument must be scalar or null');
        }

        $name = strtoupper($name);
        $group = null;
        if (strpos($name,'.')!==false) {
            list($group, $name) = explode('.', $name);
        }
        $this->name = $name;
        $this->group = $group;
        $this->setValue($value);

        foreach($parameters as $paramName => $paramValues) {

            if (!is_array($paramValues)) {
                $paramValues = array($paramValues);
            }

            foreach($paramValues as $paramValue) {
                $this->add($paramName, $paramValue);
            }

        }

    }

    /**
     * Updates the internal value
     *
     * @param string $value
     * @return void
     */
    public function setValue($value) {

        $this->value = $value;

    }

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    public function serialize() {

        $str = $this->name;
        if ($this->group) $str = $this->group . '.' . $this->name;

        foreach($this->parameters as $param) {

            $str.=';' . $param->serialize();

        }

        $src = array(
            '\\',
            "\n",
        );
        $out = array(
            '\\\\',
            '\n',
        );
        $str.=':' . str_replace($src, $out, $this->value);

        $out = '';
        while(strlen($str)>0) {
            if (strlen($str)>75) {
                $out.= mb_strcut($str,0,75,'utf-8') . "\r\n";
                $str = ' ' . mb_strcut($str,75,strlen($str),'utf-8');
            } else {
                $out.=$str . "\r\n";
                $str='';
                break;
            }
        }

        return $out;

    }

    /**
     * Adds a new componenten or element
     *
     * You can call this method with the following syntaxes:
     *
     * add(Parameter $element)
     * add(string $name, $value)
     *
     * The first version adds an Parameter
     * The second adds a property as a string.
     *
     * @param mixed $item
     * @param mixed $itemValue
     * @return void
     */
    public function add($item, $itemValue = null) {

        if ($item instanceof Parameter) {
            if (!is_null($itemValue)) {
                throw new \InvalidArgumentException('The second argument must not be specified, when passing a VObject');
            }
            $item->parent = $this;
            $this->parameters[] = $item;
        } elseif(is_string($item)) {

            $parameter = new Parameter($item,$itemValue);
            $parameter->parent = $this;
            $this->parameters[] = $parameter;

        } else {

            throw new \InvalidArgumentException('The first argument must either be a Node a string');

        }

    }

    /* ArrayAccess interface {{{ */

    /**
     * Checks if an array element exists
     *
     * @param mixed $name
     * @return bool
     */
    public function offsetExists($name) {

        if (is_int($name)) return parent::offsetExists($name);

        $name = strtoupper($name);

        foreach($this->parameters as $parameter) {
            if ($parameter->name == $name) return true;
        }
        return false;

    }

    /**
     * Returns a parameter, or parameter list.
     *
     * @param string $name
     * @return Node
     */
    public function offsetGet($name) {

        if (is_int($name)) return parent::offsetGet($name);
        $name = strtoupper($name);

        $result = array();
        foreach($this->parameters as $parameter) {
            if ($parameter->name == $name)
                $result[] = $parameter;
        }

        if (count($result)===0) {
            return null;
        } elseif (count($result)===1) {
            return $result[0];
        } else {
            $result[0]->setIterator(new ElementList($result));
            return $result[0];
        }

    }

    /**
     * Creates a new parameter
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function offsetSet($name, $value) {

        if (is_int($name)) parent::offsetSet($name, $value);

        if (is_scalar($value)) {
            if (!is_string($name))
                throw new \InvalidArgumentException('A parameter name must be specified. This means you cannot use the $array[]="string" to add parameters.');

            $this->offsetUnset($name);
            $parameter = new Parameter($name, $value);
            $parameter->parent = $this;
            $this->parameters[] = $parameter;

        } elseif ($value instanceof Parameter) {
            if (!is_null($name))
                throw new \InvalidArgumentException('Don\'t specify a parameter name if you\'re passing a \\Sabre\\VObject\\Parameter. Add using $array[]=$parameterObject.');

            $value->parent = $this;
            $this->parameters[] = $value;
        } else {
            throw new \InvalidArgumentException('You can only add parameters to the property object');
        }

    }

    /**
     * Removes one or more parameters with the specified name
     *
     * @param string $name
     * @return void
     */
    public function offsetUnset($name) {

        if (is_int($name)) parent::offsetUnset($name);
        $name = strtoupper($name);

        foreach($this->parameters as $key=>$parameter) {
            if ($parameter->name == $name) {
                $parameter->parent = null;
                unset($this->parameters[$key]);
            }

        }

    }

    /* }}} */

    /**
     * Called when this object is being cast to a string
     *
     * @return string
     */
    public function __toString() {

        return (string)$this->value;

    }

    /**
     * This method is automatically called when the object is cloned.
     * Specifically, this will ensure all child elements are also cloned.
     *
     * @return void
     */
    public function __clone() {

        foreach($this->parameters as $key=>$child) {
            $this->parameters[$key] = clone $child;
            $this->parameters[$key]->parent = $this;
        }

    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   - Node::REPAIR - If something is broken, and automatic repair may
     *                    be attempted.
     *
     * An array is returned with warnings.
     *
     * Every item in the array has the following properties:
     *    * level - (number between 1 and 3 with severity information)
     *    * message - (human readable message)
     *    * node - (reference to the offending node)
     *
     * @param int $options
     * @return array
     */
    public function validate($options = 0) {

        $warnings = array();

        // Checking if our value is UTF-8
        if (!StringUtil::isUTF8($this->value)) {
            $warnings[] = array(
                'level' => 1,
                'message' => 'Property is not valid UTF-8!',
                'node' => $this,
            );
            if ($options & self::REPAIR) {
                $this->value = StringUtil::convertToUTF8($this->value);
            }
        }

        // Checking if the propertyname does not contain any invalid bytes.
        if (!preg_match('/^([A-Z0-9-]+)$/', $this->name)) {
            $warnings[] = array(
                'level' => 1,
                'message' => 'The propertyname: ' . $this->name . ' contains invalid characters. Only A-Z, 0-9 and - are allowed',
                'node' => $this,
            );
            if ($options & self::REPAIR) {
                // Uppercasing and converting underscores to dashes.
                $this->name = strtoupper(
                    str_replace('_', '-', $this->name)
                );
                // Removing every other invalid character
                $this->name = preg_replace('/([^A-Z0-9-])/u', '', $this->name);

            }

        }

        // Validating inner parameters
        foreach($this->parameters as $param) {
            $warnings = array_merge($warnings, $param->validate($options));
        }

        return $warnings;

    }

}
