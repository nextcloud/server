<?php

/**
 * This is an authentication backend that uses a file to manage passwords.
 *
 * The backend file must conform to Apache's htdigest format
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Backend_PDO extends Sabre_DAV_Auth_Backend_AbstractDigest {

    /**
     * Reference to PDO connection
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * PDO table name we'll be using
     *
     * @var string
     */
    protected $tableName;


    /**
     * Creates the backend object.
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     *
     * @param PDO $pdo
     * @param string $tableName The PDO table name to use
     */
    public function __construct(PDO $pdo, $tableName = 'users') {

        $this->pdo = $pdo;
        $this->tableName = $tableName;

    }

    /**
     * Returns the digest hash for a user.
     *
     * @param string $realm
     * @param string $username
     * @return string|null
     */
    public function getDigestHash($realm,$username) {

        $stmt = $this->pdo->prepare('SELECT username, digesta1 FROM '.$this->tableName.' WHERE username = ?');
        $stmt->execute(array($username));
        $result = $stmt->fetchAll();

        if (!count($result)) return;

        return $result[0]['digesta1'];

    }

}
