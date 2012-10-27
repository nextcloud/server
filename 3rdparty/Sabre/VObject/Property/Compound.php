<?php

namespace Sabre\VObject\Property;

use Sabre\VObject;

/**
* Compound property.
*
* This class adds (de)serialization of compound properties to/from arrays.
*
* Currently the following properties from RFC 6350 are mapped to use this
* class:
*
*  N:          Section 6.2.2
*  ADR:        Section 6.3.1
*  ORG:        Section 6.6.4
*  CATEGORIES: Section 6.7.1
*
* In order to use this correctly, you must call setParts and getParts to
* retrieve and modify dates respectively.
*
* @author Thomas Tanghus (http://tanghus.net/)
* @author Lars Kneschke
* @author Evert Pot (http://www.rooftopsolutions.nl/)
* @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
* @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
*/

/**
* This class represents a compound property in a vCard.
*/
class Compound extends VObject\Property {

    /**
    * If property names are added to this map, they will be (de)serialised as arrays
    * using the getParts() and setParts() methods.
    * The keys are the property names, values are delimiter chars.
    *
    * @var array
    */
    static public $delimiterMap = array(
        'N'				=>	';',
        'ADR'			=>	';',
        'ORG'			=>	';',
        'CATEGORIES'	=>	',',
    );

    /**
     * The currently used delimiter.
     *
     * @var string
     */
    protected $delimiter = null;

    /**
    * Get a compound value as an array.
    *
    * @param $name string
    * @return array
    */
    public function getParts() {

        if (is_null($this->value)) {
            return array();
        }

        $delimiter = $this->getDelimiter();

        // split by any $delimiter which is NOT prefixed by a slash.
        // Note that this is not a a perfect solution. If a value is prefixed
        // by two slashes, it should actually be split anyway.
        //
        // Hopefully we can fix this better in a future version, where we can
        // break compatibility a bit.
        $compoundValues = preg_split("/(?<!\\\)$delimiter/", $this->value);

        // remove slashes from any semicolon and comma left escaped in the single values
        $compoundValues = array_map(
            function($val) {
                return strtr($val, array('\,' => ',', '\;' => ';'));
        }, $compoundValues);

        return $compoundValues;

    }

    /**
     * Returns the delimiter for this property.
     *
     * @return string
     */
    public function getDelimiter() {

        if (!$this->delimiter) {
            if (isset(self::$delimiterMap[$this->name])) {
                $this->delimiter = self::$delimiterMap[$this->name];
            } else {
                // To be a bit future proof, we are going to default the
                // delimiter to ;
                $this->delimiter = ';';
            }
        }
        return $this->delimiter;

    }

    /**
     * Set a compound value as an array.
     *
    *
    * @param $name string
    * @return array
    */
    public function setParts(array $values) {

        // add slashes to all semicolons and commas in the single values
        $values = array_map(
            function($val) {
                return strtr($val, array(',' => '\,', ';' => '\;'));
            }, $values);

        $this->setValue(
            implode($this->getDelimiter(), $values)
        );

    }

}
