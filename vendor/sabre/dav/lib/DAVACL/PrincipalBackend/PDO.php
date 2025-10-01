<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\Uri;

/**
 * PDO principal backend.
 *
 * This backend assumes all principals are in a single collection. The default collection
 * is 'principals/', but this can be overridden.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PDO extends AbstractBackend implements CreatePrincipalSupport
{
    /**
     * PDO table name for 'principals'.
     *
     * @var string
     */
    public $tableName = 'principals';

    /**
     * PDO table name for 'group members'.
     *
     * @var string
     */
    public $groupMembersTableName = 'groupmembers';

    /**
     * pdo.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * A list of additional fields to support.
     *
     * @var array
     */
    protected $fieldMap = [
        /*
         * This property can be used to display the users' real name.
         */
        '{DAV:}displayname' => [
            'dbField' => 'displayname',
        ],

        /*
         * This is the users' primary email-address.
         */
        '{http://sabredav.org/ns}email-address' => [
            'dbField' => 'email',
        ],
    ];

    /**
     * Sets up the backend.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     *
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath)
    {
        $fields = [
            'uri',
        ];

        foreach ($this->fieldMap as $key => $value) {
            $fields[] = $value['dbField'];
        }
        $result = $this->pdo->query('SELECT '.implode(',', $fields).'  FROM '.$this->tableName);

        $principals = [];

        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            // Checking if the principal is in the prefix
            list($rowPrefix) = Uri\split($row['uri']);
            if ($rowPrefix !== $prefixPath) {
                continue;
            }

            $principal = [
                'uri' => $row['uri'],
            ];
            foreach ($this->fieldMap as $key => $value) {
                if ($row[$value['dbField']]) {
                    $principal[$key] = $row[$value['dbField']];
                }
            }
            $principals[] = $principal;
        }

        return $principals;
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     *
     * @return array
     */
    public function getPrincipalByPath($path)
    {
        $fields = [
            'id',
            'uri',
        ];

        foreach ($this->fieldMap as $key => $value) {
            $fields[] = $value['dbField'];
        }
        $stmt = $this->pdo->prepare('SELECT '.implode(',', $fields).'  FROM '.$this->tableName.' WHERE uri = ?');
        $stmt->execute([$path]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }

        $principal = [
            'id' => $row['id'],
            'uri' => $row['uri'],
        ];
        foreach ($this->fieldMap as $key => $value) {
            if ($row[$value['dbField']]) {
                $principal[$key] = $row[$value['dbField']];
            }
        }

        return $principal;
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $path
     */
    public function updatePrincipal($path, DAV\PropPatch $propPatch)
    {
        $propPatch->handle(array_keys($this->fieldMap), function ($properties) use ($path) {
            $query = 'UPDATE '.$this->tableName.' SET ';
            $first = true;

            $values = [];

            foreach ($properties as $key => $value) {
                $dbField = $this->fieldMap[$key]['dbField'];

                if (!$first) {
                    $query .= ', ';
                }
                $first = false;
                $query .= $dbField.' = :'.$dbField;
                $values[$dbField] = $value;
            }

            $query .= ' WHERE uri = :uri';
            $values['uri'] = $path;

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($values);

            return true;
        });
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param string $test
     *
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        if (0 == count($searchProperties)) {
            return [];
        }    //No criteria

        $query = 'SELECT uri FROM '.$this->tableName.' WHERE ';
        $values = [];
        foreach ($searchProperties as $property => $value) {
            switch ($property) {
                case '{DAV:}displayname':
                    $column = 'displayname';
                    break;
                case '{http://sabredav.org/ns}email-address':
                    $column = 'email';
                    break;
                default:
                    // Unsupported property
                    return [];
            }
            if (count($values) > 0) {
                $query .= (0 == strcmp($test, 'anyof') ? ' OR ' : ' AND ');
            }
            $query .= 'lower('.$column.') LIKE lower(?)';
            $values[] = '%'.$value.'%';
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);

        $principals = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // Checking if the principal is in the prefix
            list($rowPrefix) = Uri\split($row['uri']);
            if ($rowPrefix !== $prefixPath) {
                continue;
            }

            $principals[] = $row['uri'];
        }

        return $principals;
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     *
     * @return string
     */
    public function findByUri($uri, $principalPrefix)
    {
        $uriParts = Uri\parse($uri);

        // Only two types of uri are supported :
        //   - the "mailto:" scheme with some non-empty address
        //   - a principals uri, in the form "principals/NAME"
        // In both cases, `path` must not be empty.
        if (empty($uriParts['path'])) {
            return null;
        }

        $uri = null;
        if ('mailto' === $uriParts['scheme']) {
            $query = 'SELECT uri FROM '.$this->tableName.' WHERE lower(email)=lower(?)';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$uriParts['path']]);

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                // Checking if the principal is in the prefix
                list($rowPrefix) = Uri\split($row['uri']);
                if ($rowPrefix !== $principalPrefix) {
                    continue;
                }

                $uri = $row['uri'];
                break; //Stop on first match
            }
        } else {
            $pathParts = Uri\split($uriParts['path']); // We can do this since $uriParts['path'] is not null

            if (2 === count($pathParts) && $pathParts[0] === $principalPrefix) {
                // Checking that this uri exists
                $query = 'SELECT * FROM '.$this->tableName.' WHERE uri = ?';
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$uriParts['path']]);
                $rows = $stmt->fetchAll();

                if (count($rows) > 0) {
                    $uri = $uriParts['path'];
                }
            }
        }

        return $uri;
    }

    /**
     * Returns the list of members for a group-principal.
     *
     * @param string $principal
     *
     * @return array
     */
    public function getGroupMemberSet($principal)
    {
        $principal = $this->getPrincipalByPath($principal);
        if (!$principal) {
            throw new DAV\Exception('Principal not found');
        }
        $stmt = $this->pdo->prepare('SELECT principals.uri as uri FROM '.$this->groupMembersTableName.' AS groupmembers LEFT JOIN '.$this->tableName.' AS principals ON groupmembers.member_id = principals.id WHERE groupmembers.principal_id = ?');
        $stmt->execute([$principal['id']]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['uri'];
        }

        return $result;
    }

    /**
     * Returns the list of groups a principal is a member of.
     *
     * @param string $principal
     *
     * @return array
     */
    public function getGroupMembership($principal)
    {
        $principal = $this->getPrincipalByPath($principal);
        if (!$principal) {
            throw new DAV\Exception('Principal not found');
        }
        $stmt = $this->pdo->prepare('SELECT principals.uri as uri FROM '.$this->groupMembersTableName.' AS groupmembers LEFT JOIN '.$this->tableName.' AS principals ON groupmembers.principal_id = principals.id WHERE groupmembers.member_id = ?');
        $stmt->execute([$principal['id']]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['uri'];
        }

        return $result;
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     */
    public function setGroupMemberSet($principal, array $members)
    {
        // Grabbing the list of principal id's.
        $stmt = $this->pdo->prepare('SELECT id, uri FROM '.$this->tableName.' WHERE uri IN (? '.str_repeat(', ? ', count($members)).');');
        $stmt->execute(array_merge([$principal], $members));

        $memberIds = [];
        $principalId = null;

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['uri'] == $principal) {
                $principalId = $row['id'];
            } else {
                $memberIds[] = $row['id'];
            }
        }
        if (!$principalId) {
            throw new DAV\Exception('Principal not found');
        }
        // Wiping out old members
        $stmt = $this->pdo->prepare('DELETE FROM '.$this->groupMembersTableName.' WHERE principal_id = ?;');
        $stmt->execute([$principalId]);

        foreach ($memberIds as $memberId) {
            $stmt = $this->pdo->prepare('INSERT INTO '.$this->groupMembersTableName.' (principal_id, member_id) VALUES (?, ?);');
            $stmt->execute([$principalId, $memberId]);
        }
    }

    /**
     * Creates a new principal.
     *
     * This method receives a full path for the new principal. The mkCol object
     * contains any additional webdav properties specified during the creation
     * of the principal.
     *
     * @param string $path
     */
    public function createPrincipal($path, MkCol $mkCol)
    {
        $stmt = $this->pdo->prepare('INSERT INTO '.$this->tableName.' (uri) VALUES (?)');
        $stmt->execute([$path]);
        $this->updatePrincipal($path, $mkCol);
    }
}
