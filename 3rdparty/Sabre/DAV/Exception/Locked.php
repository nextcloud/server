<?php

/**
 * Locked
 *
 * The 423 is thrown when a client tried to access a resource that was locked, without supplying a valid lock token
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_Locked extends Sabre_DAV_Exception {

    /**
     * Lock information
     *
     * @var Sabre_DAV_Locks_LockInfo
     */
    protected $lock;

    /**
     * Creates the exception
     *
     * A LockInfo object should be passed if the user should be informed
     * which lock actually has the file locked.
     *
     * @param Sabre_DAV_Locks_LockInfo $lock
     */
    public function __construct(Sabre_DAV_Locks_LockInfo $lock = null) {

        $this->lock = $lock;

    }

    /**
     * Returns the HTTP statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 423;

    }

    /**
     * This method allows the exception to include additional information into the WebDAV error response
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $errorNode
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        if ($this->lock) {
            $error = $errorNode->ownerDocument->createElementNS('DAV:','d:lock-token-submitted');
            $errorNode->appendChild($error);
            if (!is_object($this->lock)) var_dump($this->lock);
            $error->appendChild($errorNode->ownerDocument->createElementNS('DAV:','d:href',$this->lock->uri));
        }

    }

}

