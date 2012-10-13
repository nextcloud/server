<?php

/**
 * PropertyInterface
 *
 * Implement this interface to create new complex properties
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_PropertyInterface {

    public function serialize(Sabre_DAV_Server $server, DOMElement $prop);

    static function unserialize(DOMElement $prop); 

}

