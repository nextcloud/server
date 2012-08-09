<?php

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a single file.
 *
 * Note that this is not nearly as robust as a database, you are encouraged
 * to use the PDO backend instead.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Locks_Backend_File extends Sabre_DAV_Locks_Backend_Abstract {

    /**
     * The storage file
     *
     * @var string
     */
    private $locksFile;

    /**
     * Constructor
     *
     * @param string $locksFile path to file
     */
    public function __construct($locksFile) {

        $this->locksFile = $locksFile;

    }

    /**
     * Returns a list of Sabre_DAV_Locks_LockInfo objects
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    public function getLocks($uri, $returnChildLocks) {

        $newLocks = array();

        $locks = $this->getData();

        foreach($locks as $lock) {

            if ($lock->uri === $uri ||
                //deep locks on parents
                ($lock->depth!=0 && strpos($uri, $lock->uri . '/')===0) ||

                // locks on children
                ($returnChildLocks && (strpos($lock->uri, $uri . '/')===0)) ) {

                $newLocks[] = $lock;

            }

        }

        // Checking if we can remove any of these locks
        foreach($newLocks as $k=>$lock) {
            if (time() > $lock->timeout + $lock->created) unset($newLocks[$k]);
        }
        return $newLocks;

    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function lock($uri, Sabre_DAV_Locks_LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getData();

        foreach($locks as $k=>$lock) {
            if (
                ($lock->token == $lockInfo->token) ||
                (time() > $lock->timeout + $lock->created)
            ) {
                unset($locks[$k]);
            }
        }
        $locks[] = $lockInfo;
        $this->putData($locks);
        return true;

    }

    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function unlock($uri, Sabre_DAV_Locks_LockInfo $lockInfo) {

        $locks = $this->getData();
        foreach($locks as $k=>$lock) {

            if ($lock->token == $lockInfo->token) {

                unset($locks[$k]);
                $this->putData($locks);
                return true;

            }
        }
        return false;

    }

    /**
     * Loads the lockdata from the filesystem.
     *
     * @return array
     */
    protected function getData() {

        if (!file_exists($this->locksFile)) return array();

        // opening up the file, and creating a shared lock
        $handle = fopen($this->locksFile,'r');
        flock($handle,LOCK_SH);

        // Reading data until the eof
        $data = stream_get_contents($handle);

        // We're all good
        fclose($handle);

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        if (!$data) return array();
        return $data;

    }

    /**
     * Saves the lockdata
     *
     * @param array $newData
     * @return void
     */
    protected function putData(array $newData) {

        // opening up the file, and creating an exclusive lock
        $handle = fopen($this->locksFile,'a+');
        flock($handle,LOCK_EX);

        // We can only truncate and rewind once the lock is acquired.
        ftruncate($handle,0);
        rewind($handle);

        fwrite($handle,serialize($newData));
        fclose($handle);

    }

}

