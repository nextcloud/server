<?php

/**
 * Implement this class to support locking 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_ILockable extends Sabre_DAV_INode {

    /**
     * Returns an array with locks currently on the node 
     * 
     * @return Sabre_DAV_Locks_LockInfo[] 
     */
    function getLocks();

    /**
     * Creates a new lock on the file.  
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo The lock information 
     * @return void
     */
    function lock(Sabre_DAV_Locks_LockInfo $lockInfo);

    /**
     * Unlocks a file 
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo The lock information 
     * @return void 
     */
    function unlock(Sabre_DAV_Locks_LockInfo $lockInfo);

}

