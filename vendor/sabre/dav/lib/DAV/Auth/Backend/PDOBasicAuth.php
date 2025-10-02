<?php

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses a database to manage passwords.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PDOBasicAuth extends AbstractBasic
{
    /**
     * Reference to PDO connection.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * PDO table name we'll be using.
     *
     * @var string
     */
    protected $tableName;

    /**
     * PDO digest column name we'll be using
     * (i.e. digest, password, password_hash).
     *
     * @var string
     */
    protected $digestColumn;

    /**
     * PDO uuid(unique user identifier) column name we'll be using
     * (i.e. username, email).
     *
     * @var string
     */
    protected $uuidColumn;

    /**
     * Digest prefix:
     * if the backend you are using for is prefixing
     * your password hashes set this option to your prefix to
     * cut it off before verifying.
     *
     * @var string
     */
    protected $digestPrefix;

    /**
     * Creates the backend object.
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     */
    public function __construct(\PDO $pdo, array $options = [])
    {
        $this->pdo = $pdo;
        if (isset($options['tableName'])) {
            $this->tableName = $options['tableName'];
        } else {
            $this->tableName = 'users';
        }
        if (isset($options['digestColumn'])) {
            $this->digestColumn = $options['digestColumn'];
        } else {
            $this->digestColumn = 'digest';
        }
        if (isset($options['uuidColumn'])) {
            $this->uuidColumn = $options['uuidColumn'];
        } else {
            $this->uuidColumn = 'username';
        }
        if (isset($options['digestPrefix'])) {
            $this->digestPrefix = $options['digestPrefix'];
        }
    }

    /**
     * Validates a username and password.
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function validateUserPass($username, $password)
    {
        $stmt = $this->pdo->prepare('SELECT '.$this->digestColumn.' FROM '.$this->tableName.' WHERE '.$this->uuidColumn.' = ?');
        $stmt->execute([$username]);
        $result = $stmt->fetchAll();

        if (!count($result)) {
            return false;
        } else {
            $digest = $result[0][$this->digestColumn];

            if (isset($this->digestPrefix)) {
                $digest = substr($digest, strlen($this->digestPrefix));
            }

            if (password_verify($password, $digest)) {
                return true;
            }

            return false;
        }
    }
}
