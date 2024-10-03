<?php
namespace OCA\SocialLogin\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db;
use OCP\AppFramework\Db\QBMapper;

class ConnectedLoginMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sociallogin_connect', ConnectedLogin::class);
    }

    /**
     * @param string $identifier social login identifier
     * @return ConnectedLogin|null
     */
    public function find(string $identifier) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                    $qb->expr()->eq('identifier', $qb->createNamedParameter($identifier))
            );

        try {
            return $this->findEntity($qb);
        } catch(Db\DoesNotExistException $e) {
            return null;
        } catch(Db\MultipleObjectsReturnedException $e) {
            return null;
        }
    }

    /**
     * @param string $identifier social login identifier
     * @return string|null Nextcloud user id that corresponds to the social login identifier
     */
    public function findUID($identifier)
    {
        $login = $this->find($identifier);
        return $login == null ? null : $login->uid;
    }

    /**
     * @param string $uid Nextcloud user id
     * @param string $identifier social login identifier
     */
    public function connectLogin($uid, $identifier)
    {
        $l = new ConnectedLogin();
        $l->setUid($uid);
        $l->setIdentifier($identifier);
        $this->insert($l);
    }

    /**
     * @param string $identifier social login identifier
     */
    public function disconnectLogin($identifier)
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->tableName)
            ->where(
                $qb->expr()->eq('identifier', $qb->createNamedParameter($identifier))
            )
        ;
        if (method_exists($qb, 'executeStatement')) {
            $qb->executeStatement();
        } else {
            $qb->execute();
        }
    }

    /**
     * @param string $uid Nextcloud user id
     */
    public function disconnectAll($uid)
    {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->tableName)
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid))
            )
        ;
        if (method_exists($qb, 'executeStatement')) {
            $qb->executeStatement();
        } else {
            $qb->execute();
        }
    }

    /**
     * @param string $uid
     * @return array containing the social login identifiers of all connected logins
     */
    public function getConnectedLogins($uid)
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));

        $entities = $this->findEntities($qb);
        $result = [];
        foreach ($entities as $e) {
            $result[] = $e->identifier;
        }

        return $result;
    }

}
