<?php

/**
 * VObject Parameter
 *
 * This class represents a parameter. A parameter is always tied to a property.
 * In the case of:
 *   DTSTART;VALUE=DATE:20101108
 * VALUE=DATE would be the parameter name and value.
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Parameter extends Sabre_VObject_Node {

    /**
     * Parameter name
     *
     * @var string
     */
    public $name;

    /**
     * Parameter value
     *
     * @var string
     */
    public $value;

    /**
     * Sets up the object
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value = null) {

        $this->name = strtoupper($name);
        $this->value = $value;

    }

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    public function serialize() {

        if (is_null($this->value)) {
            return $this->name;
        }
        $src = array(
            '\\',
            "\n",
            ';',
            ',',
        );
        $out = array(
            '\\\\',
            '\n',
            '\;',
            '\,',
        );

        return $this->name . '=' . str_replace($src, $out, $this->value);

    }

    /**
     * Called when this object is being cast to a string
     *
     * @return string
     */
    public function __toString() {

        return $this->value;

    }

}
