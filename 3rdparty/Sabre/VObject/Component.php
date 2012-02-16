<?php

/**
 * VObject Component
 *
 * This class represents a VCALENDAR/VCARD component. A component is for example
 * VEVENT, VTODO and also VCALENDAR. It starts with BEGIN:COMPONENTNAME and 
 * ends with END:COMPONENTNAME
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component extends Sabre_VObject_Element {

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
     * Creates a new component.
     *
     * By default this object will iterate over its own children, but this can 
     * be overridden with the iterator argument
     * 
     * @param string $name 
     * @param Sabre_VObject_ElementList $iterator
     */
    public function __construct($name, Sabre_VObject_ElementList $iterator = null) {

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
        foreach($this->children as $child) $str.=$child->serialize();
        $str.= "END:" . $this->name . "\r\n";
        
        return $str;

    }


    /**
     * Adds a new componenten or element
     *
     * You can call this method with the following syntaxes:
     *
     * add(Sabre_VObject_Element $element)
     * add(string $name, $value)
     *
     * The first version adds an Element
     * The second adds a property as a string. 
     * 
     * @param mixed $item 
     * @param mixed $itemValue 
     * @return void
     */
    public function add($item, $itemValue = null) {

        if ($item instanceof Sabre_VObject_Element) {
            if (!is_null($itemValue)) {
                throw new InvalidArgumentException('The second argument must not be specified, when passing a VObject');
            }
            $item->parent = $this;
            $this->children[] = $item;
        } elseif(is_string($item)) {

            if (!is_scalar($itemValue)) {
                throw new InvalidArgumentException('The second argument must be scalar');
            }
            $item = new Sabre_VObject_Property($item,$itemValue);
            $item->parent = $this;
            $this->children[] = $item;

        } else {
            
            throw new InvalidArgumentException('The first argument must either be a Sabre_VObject_Element or a string');

        }

    }

    /**
     * Returns an iterable list of children 
     * 
     * @return Sabre_VObject_ElementList 
     */
    public function children() {

        return new Sabre_VObject_ElementList($this->children);

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
                (is_null($group) || ( $child instanceof Sabre_VObject_Property && strtoupper($child->group) === $group))
            ) {
                
                $result[$key] = $child;

            }
        }

        reset($result);
        return $result;

    }

    /* Magic property accessors {{{ */

    /**
     * Using 'get' you will either get a propery or component, 
     *
     * If there were no child-elements found with the specified name,
     * null is returned.
     * 
     * @param string $name 
     * @return void
     */
    public function __get($name) {

        $matches = $this->select($name);
        if (count($matches)===0) {
            return null;
        } else {
            $firstMatch = current($matches);
            $firstMatch->setIterator(new Sabre_VObject_ElementList(array_values($matches)));
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
     * You can either pass a Sabre_VObject_Component, Sabre_VObject_Property
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

        if ($value instanceof Sabre_VObject_Component || $value instanceof Sabre_VObject_Property) {
            $value->parent = $this;
            if (!is_null($overWrite)) {
                $this->children[$overWrite] = $value;
            } else {
                $this->children[] = $value;
            }
        } elseif (is_scalar($value)) {
            $property = new Sabre_VObject_Property($name,$value);
            $property->parent = $this;
            if (!is_null($overWrite)) {
                $this->children[$overWrite] = $property;
            } else {
                $this->children[] = $property;
            }
        } else {
            throw new InvalidArgumentException('You must pass a Sabre_VObject_Component, Sabre_VObject_Property or scalar type');
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

}
