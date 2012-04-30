<?php

/**
 * LockInfo class
 *
 * An object of the LockInfo class holds all the information relevant to a
 * single lock.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Locks_LockInfo {

    /**
     * A shared lock
     */
    const SHARED = 1;

    /**
     * An exclusive lock
     */
    const EXCLUSIVE = 2;

    /**
     * A never expiring timeout
     */
    const TIMEOUT_INFINITE = -1;

    /**
     * The owner of the lock
     *
     * @var string
     */
    public $owner;

    /**
     * The locktoken
     *
     * @var string
     */
    public $token;

    /**
     * How long till the lock is expiring
     *
     * @var int
     */
    public $timeout;

    /**
     * UNIX Timestamp of when this lock was created
     *
     * @var int
     */
    public $created;

    /**
     * Exclusive or shared lock
     *
     * @var int
     */
    public $scope = self::EXCLUSIVE;

    /**
     * Depth of lock, can be 0 or Sabre_DAV_Server::DEPTH_INFINITY
     */
    public $depth = 0;

    /**
     * The uri this lock locks
     *
     * TODO: This value is not always set
     * @var mixed
     */
    public $uri;

}

