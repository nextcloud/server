<?php

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a database. You must pass a PDO
 * connection object in the constructor.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Locks_Backend_PDO extends Sabre_DAV_Locks_Backend_Abstract {

    /**
     * The PDO connection object
     *
     * @var pdo
     */
    private $pdo;

    /**
     * The PDO tablename this backend uses.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Constructor
     *
     * @param PDO $pdo
     * @param string $tableName
     */
    public function __construct(PDO $pdo, $tableName = 'locks') {

        $this->pdo = $pdo;
        $this->tableName = $tableName;

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

        // NOTE: the following 10 lines or so could be easily replaced by
        // pure sql. MySQL's non-standard string concatenation prevents us
        // from doing this though.
        $query = 'SELECT owner, token, timeout, created, scope, depth, uri FROM '.$this->tableName.' WHERE ((created + timeout) > CAST(? AS UNSIGNED INTEGER)) AND ((uri = ?)';
        $params = array(time(),$uri);

        // We need to check locks for every part in the uri.
        $uriParts = explode('/',$uri);

        // We already covered the last part of the uri
        array_pop($uriParts);

        $currentPath='';

        foreach($uriParts as $part) {

            if ($currentPath) $currentPath.='/';
            $currentPath.=$part;

            $query.=' OR (depth!=0 AND uri = ?)';
            $params[] = $currentPath;

        }

        if ($returnChildLocks) {

            $query.=' OR (uri LIKE ?)';
            $params[] = $uri . '/%';

        }
        $query.=')';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        $lockList = array();
        foreach($result as $row) {

            $lockInfo = new Sabre_DAV_Locks_LockInfo();
            $lockInfo->owner = $row['owner'];
            $lockInfo->token = $row['token'];
            $lockInfo->timeout = $row['timeout'];
            $lockInfo->created = $row['created'];
            $lockInfo->scope = $row['scope'];
            $lockInfo->depth = $row['depth'];
            $lockInfo->uri   = $row['uri'];
            $lockList[] = $lockInfo;

        }

        return $lockList;

    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function lock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 30*60;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getLocks($uri,false);
        $exists = false;
        foreach($locks as $lock) {
            if ($lock->token == $lockInfo->token) $exists = true;
        }

        if ($exists) {
            $stmt = $this->pdo->prepare('UPDATE '.$this->tableName.' SET owner = ?, timeout = ?, scope = ?, depth = ?, uri = ?, created = ? WHERE token = ?');
            $stmt->execute(array($lockInfo->owner,$lockInfo->timeout,$lockInfo->scope,$lockInfo->depth,$uri,$lockInfo->created,$lockInfo->token));
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO '.$this->tableName.' (owner,timeout,scope,depth,uri,created,token) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute(array($lockInfo->owner,$lockInfo->timeout,$lockInfo->scope,$lockInfo->depth,$uri,$lockInfo->created,$lockInfo->token));
        }

        return true;

    }



    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function unlock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        $stmt = $this->pdo->prepare('DELETE FROM '.$this->tableName.' WHERE uri = ? AND token = ?');
        $stmt->execute(array($uri,$lockInfo->token));

        return $stmt->rowCount()===1;

    }

}

