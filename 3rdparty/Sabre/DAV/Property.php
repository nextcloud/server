<?php

/**
 * Abstract property class
 *
 * Extend this class to create custom complex properties
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Property {

    abstract function serialize(Sabre_DAV_Server $server, DOMElement $prop);

    static function unserialize(DOMElement $prop) {

        throw new Sabre_DAV_Exception('Unserialize has not been implemented for this class');

    }

}

