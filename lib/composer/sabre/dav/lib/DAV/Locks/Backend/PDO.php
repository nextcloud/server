<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks\LockInfo;

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a database. You must pass a PDO
 * connection object in the constructor.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PDO extends AbstractBackend
{
    /**
     * The PDO tablename this backend uses.
     *
     * @var string
     */
    public $tableName = 'locks';

    /**
     * The PDO connection object.
     *
     * @var pdo
     */
    protected $pdo;

    /**
     * Constructor.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a list of Sabre\DAV\Locks\LockInfo objects.
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool   $returnChildLocks
     *
     * @return array
     */
    public function getLocks($uri, $returnChildLocks)
    {
        // NOTE: the following 10 lines or so could be easily replaced by
        // pure sql. MySQL's non-standard string concatenation prevents us
        // from doing this though.
        $query = 'SELECT owner, token, timeout, created, scope, depth, uri FROM '.$this->tableName.' WHERE (created > (? - timeout)) AND ((uri = ?)';
        $params = [time(), $uri];

        // We need to check locks for every part in the uri.
        $uriParts = explode('/', $uri);

        // We already covered the last part of the uri
        array_pop($uriParts);

        $currentPath = '';

        foreach ($uriParts as $part) {
            if ($currentPath) {
                $currentPath .= '/';
            }
            $currentPath .= $part;

            $query .= ' OR (depth!=0 AND uri = ?)';
            $params[] = $currentPath;
        }

        if ($returnChildLocks) {
            $query .= ' OR (uri LIKE ?)';
            $params[] = $uri.'/%';
        }
        $query .= ')';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        $lockList = [];
        foreach ($result as $row) {
            $lockInfo = new LockInfo();
            $lockInfo->owner = $row['owner'];
            $lockInfo->token = $row['token'];
            $lockInfo->timeout = $row['timeout'];
            $lockInfo->created = $row['created'];
            $lockInfo->scope = $row['scope'];
            $lockInfo->depth = $row['depth'];
            $lockInfo->uri = $row['uri'];
            $lockList[] = $lockInfo;
        }

        return $lockList;
    }

    /**
     * Locks a uri.
     *
     * @param string $uri
     *
     * @return bool
     */
    public function lock($uri, LockInfo $lockInfo)
    {
        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 30 * 60;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getLocks($uri, false);
        $exists = false;
        foreach ($locks as $lock) {
            if ($lock->token == $lockInfo->token) {
                $exists = true;
            }
        }

        if ($exists) {
            $stmt = $this->pdo->prepare('UPDATE '.$this->tableName.' SET owner = ?, timeout = ?, scope = ?, depth = ?, uri = ?, created = ? WHERE token = ?');
            $stmt->execute([
                $lockInfo->owner,
                $lockInfo->timeout,
                $lockInfo->scope,
                $lockInfo->depth,
                $uri,
                $lockInfo->created,
                $lockInfo->token,
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO '.$this->tableName.' (owner,timeout,scope,depth,uri,created,token) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([
                $lockInfo->owner,
                $lockInfo->timeout,
                $lockInfo->scope,
                $lockInfo->depth,
                $uri,
                $lockInfo->created,
                $lockInfo->token,
            ]);
        }

        return true;
    }

    /**
     * Removes a lock from a uri.
     *
     * @param string $uri
     *
     * @return bool
     */
    public function unlock($uri, LockInfo $lockInfo)
    {
        $stmt = $this->pdo->prepare('DELETE FROM '.$this->tableName.' WHERE uri = ? AND token = ?');
        $stmt->execute([$uri, $lockInfo->token]);

        return 1 === $stmt->rowCount();
    }
}
