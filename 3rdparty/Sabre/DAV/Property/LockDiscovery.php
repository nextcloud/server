<?php

/**
 * Represents {DAV:}lockdiscovery property
 *
 * This property contains all the open locks on a given resource
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_LockDiscovery extends Sabre_DAV_Property {

    /**
     * locks
     *
     * @var array
     */
    public $locks;

    /**
     * Should we show the locktoken as well?
     *
     * @var bool
     */
    public $revealLockToken;

    /**
     * Hides the {DAV:}lockroot element from the response.
     *
     * It was reported that showing the lockroot in the response can break
     * Office 2000 compatibility.
     */
    static public $hideLockRoot = false;

    /**
     * __construct
     *
     * @param array $locks
     * @param bool $revealLockToken
     */
    public function __construct($locks, $revealLockToken = false) {

        $this->locks = $locks;
        $this->revealLockToken = $revealLockToken;

    }

    /**
     * serialize
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement       $prop
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, DOMElement $prop) {

        $doc = $prop->ownerDocument;

        foreach($this->locks as $lock) {

            $activeLock = $doc->createElementNS('DAV:','d:activelock');
            $prop->appendChild($activeLock);

            $lockScope = $doc->createElementNS('DAV:','d:lockscope');
            $activeLock->appendChild($lockScope);

            $lockScope->appendChild($doc->createElementNS('DAV:','d:' . ($lock->scope==Sabre_DAV_Locks_LockInfo::EXCLUSIVE?'exclusive':'shared')));

            $lockType = $doc->createElementNS('DAV:','d:locktype');
            $activeLock->appendChild($lockType);

            $lockType->appendChild($doc->createElementNS('DAV:','d:write'));

            /* {DAV:}lockroot */
            if (!self::$hideLockRoot) {
                $lockRoot = $doc->createElementNS('DAV:','d:lockroot');
                $activeLock->appendChild($lockRoot);
                $href = $doc->createElementNS('DAV:','d:href');
                $href->appendChild($doc->createTextNode($server->getBaseUri() . $lock->uri));
                $lockRoot->appendChild($href);
            }

            $activeLock->appendChild($doc->createElementNS('DAV:','d:depth',($lock->depth == Sabre_DAV_Server::DEPTH_INFINITY?'infinity':$lock->depth)));
            $activeLock->appendChild($doc->createElementNS('DAV:','d:timeout','Second-' . $lock->timeout));

            if ($this->revealLockToken) {
                $lockToken = $doc->createElementNS('DAV:','d:locktoken');
                $activeLock->appendChild($lockToken);
                $lockToken->appendChild($doc->createElementNS('DAV:','d:href','opaquelocktoken:' . $lock->token));
            }

            $activeLock->appendChild($doc->createElementNS('DAV:','d:owner',$lock->owner));

        }

    }

}

