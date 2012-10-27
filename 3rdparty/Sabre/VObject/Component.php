<?php

namespace Sabre\VObject;

/**
 * VObject Component
 *
 * This class represents a VCALENDAR/VCARD component. A component is for example
 * VEVENT, VTODO and also VCALENDAR. It starts with BEGIN:COMPONENTNAME and
 * ends with END:COMPONENTNAME
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Component extends Node {

    /**
     * Name, for example VEVENT
     *
     * @var string
     */
    public $name;

    /**
     * Children properties and components
     *
     * @var array
     */
    public $children = array();

    /**
     * If components are added to this map, they will be automatically mapped
     * to their respective classes, if parsed by the reader or constructed with
     * the 'create' method.
     *
     * @var array
     */
    static public $classMap = array(
        'VALARM'        => 'Sabre\\VObject\\Component\\VAlarm',
        'VCALENDAR'     => 'Sabre\\VObject\\Component\\VCalendar',
        'VCARD'         => 'Sabre\\VObject\\Component\\VCard',
        'VEVENT'        => 'Sabre\\VObject\\Component\\VEvent',
        'VJOURNAL'      => 'Sabre\\VObject\\Component\\VJournal',
        'VTODO'         => 'Sabre\\VObject\\Component\\VTodo',
        'VFREEBUSY'     => 'Sabre\\VObject\\Component\\VFreeBusy',
    );

    /**
     * Creates the new component by name, but in addition will also see if
     * there's a class mapped to the property name.
     *
     * @param string $name
     * @param string $value
     * @return Component
     */
    static public function create($name, $value = null) {

        $name = strtoupper($name);

        if (isset(self::$classMap[$name])) {
            return new self::$classMap[$name]($name, $value);
        } else {
            return new self($name, $value);
        }

    }

    /**
     * Creates a new component.
     *
     * By default this object will iterate over its own children, but this can
     * be overridden with the iterator argument
     *
     * @param string $name
     * @param ElementList $iterator
     */
    public function __construct($name, ElementList $iterator = null) {

        $this->name = strtoupper($name);
        if (!is_null($iterator)) $this->iterator = $iterator;

    }

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    public function serialize() {

        $str = "BEGIN:" . $this->name . "\r\n";

        /**
         * Gives a component a 'score' for sorting purposes.
         *
         * This is solely used by the childrenSort method.
         *
         * A higher score means the item will be lower in the list.
         * To avoid score collisions, each "score category" has a reasonable
         * space to accomodate elements. The $key is added to the $score to
         * preserve the original relative order of elements.
         *
         * @param int $key
         * @param array $array
         * @return int
         */
        $sortScore = function($key, $array) {

            if ($array[$key] instanceof Component) {

                // We want to encode VTIMEZONE first, this is a personal
                // preference.
                if ($array[$key]->name === 'VTIMEZONE') {
                    $score=300000000;
                    return $score+$key;
                } else {
                    $score=400000000;
                    return $score+$key;
                }
            } else {
                // Properties get encoded first
                // VCARD version 4.0 wants the VERSION property to appear first
                if ($array[$key] instanceof Property) {
                    if ($array[$key]->name === 'VERSION') {
                        $score=100000000;
                        return $score+$key;
                    } else {
                        // All other properties
                        $score=200000000;
                        return $score+$key;
                    }
                }
            }

        };

        $tmp = $this->children;
        uksort($this->children, function($a, $b) use ($sortScore, $tmp) {

            $sA = $sortScore($a, $tmp);
            $sB = $sortScore($b, $tmp);

            if ($sA === $sB) return 0;

            return ($sA < $sB) ? -1 : 1;

        });

        foreach($this->children as $child) $str.=$child->serialize();
        $str.= "END:" . $this->name . "\r\n";

        return $str;

    }

    /**
     * Adds a new component or element
     *
     * You can call this method with the following syntaxes:
     *
     * add(Node $node)
     * add(string $name, $value, array $parameters = array())
     *
     * The first version adds an Element
     * The second adds a property as a string.
     *
     * @param mixed $item
     * @param mixed $itemValue
     * @return void
     */
    public function add($item, $itemValue = null, array $parameters = array()) {

        if ($item instanceof Node) {
            if (!is_null($itemValue)) {
                throw new \InvalidArgumentException('The second argument must not be specified, when passing a VObject Node');
            }
            $item->parent = $this;
            $this->children[] = $item;
        } elseif(is_string($item)) {

            $item = Property::create($item,$itemValue, $parameters);
            $item->parent = $this;
            $this->children[] = $item;

        } else {

            throw new \InvalidArgumentException('The first argument must either be a \\Sabre\\VObject\\Node or a string');

        }

    }

    /**
     * Returns an iterable list of children
     *
     * @return ElementList
     */
    public function children() {

        return new ElementList($this->children);

    }

    /**
     * Returns an array with elements that match the specified name.
     *
     * This function is also aware of MIME-Directory groups (as they appear in
     * vcards). This means that if a property is grouped as "HOME.EMAIL", it
     * will also be returned when searching for just "EMAIL". If you want to
     * search for a property in a specific group, you can select on the entire
     * string ("HOME.EMAIL"). If you want to search on a specific property that
     * has not been assigned a group, specify ".EMAIL".
     *
     * Keys are retained from the 'children' array, which may be confusing in
     * certain cases.
     *
     * @param string $name
     * @return array
     */
    public function select($name) {

        $group = null;
        $name = strtoupper($name);
        if (strpos($name,'.')!==false) {
            list($group,$name) = explode('.', $name, 2);
        }

        $result = array();
        foreach($this->children as $key=>$child) {

            if (
                strtoupper($child->name) === $name &&
                (is_null($group) || ( $child instanceof Property && strtoupper($child->group) === $group))
            ) {

                $result[$key] = $child;

            }
        }

        reset($result);
        return $result;

    }

    /**
     * This method only returns a list of sub-components. Properties are
     * ignored.
     *
     * @return array
     */
    public function getComponents() {

        $result = array();
        foreach($this->children as $child) {
            if ($child instanceof Component) {
                $result[] = $child;
            }
        }

        return $result;

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

        $result = array();
        foreach($this->children as $child) {
            $result = array_merge($result, $child->validate($options));
        }
        return $result;

    }

    /* Magic property accessors {{{ */

    /**
     * Using 'get' you will either get a property or component,
     *
     * If there were no child-elements found with the specified name,
     * null is returned.
     *
     * @param string $name
     * @return Property
     */
    public function __get($name) {

        $matches = $this->select($name);
        if (count($matches)===0) {
            return null;
        } else {
            $firstMatch = current($matches);
            /** @var $firstMatch Property */
            $firstMatch->setIterator(new ElementList(array_values($matches)));
            return $firstMatch;
        }

    }

    /**
     * This method checks if a sub-element with the specified name exists.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {

        $matches = $this->select($name);
        return count($matches)>0;

    }

    /**
     * Using the setter method you can add properties or subcomponents
     *
     * You can either pass a Component, Property
     * object, or a string to automatically create a Property.
     *
     * If the item already exists, it will be removed. If you want to add
     * a new item with the same name, always use the add() method.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) {

        $matches = $this->select($name);
        $overWrite = count($matches)?key($matches):null;

        if ($value instanceof Component || $value instanceof Property) {
            $value->parent = $this;
            if (!is_null($overWrite)) {
                $this->children[$overWrite] = $value;
            } else {
                $this->children[] = $value;
            }
        } elseif (is_scalar($value)) {
            $property = Property::create($name,$value);
            $property->parent = $this;
            if (!is_null($overWrite)) {
                $this->children[$overWrite] = $property;
            } else {
                $this->children[] = $property;
            }
        } else {
            throw new \InvalidArgumentException('You must pass a \\Sabre\\VObject\\Component, \\Sabre\\VObject\\Property or scalar type');
        }

    }

    /**
     * Removes all properties and components within this component.
     *
     * @param string $name
     * @return void
     */
    public function __unset($name) {

        $matches = $this->select($name);
        foreach($matches as $k=>$child) {

            unset($this->children[$k]);
            $child->parent = null;

        }

    }

    /* }}} */

    /**
     * This method is automatically called when the object is cloned.
     * Specifically, this will ensure all child elements are also cloned.
     *
     * @return void
     */
    public function __clone() {

        foreach($this->children as $key=>$child) {
            $this->children[$key] = clone $child;
            $this->children[$key]->parent = $this;
        }

    }

}
