<?php

/**#@+
 * Constants
 */
/**
 * Maximum number of connection per user per server
 */
define("MAX_NUM_CONN_PER_USER_SERVER", 5);
/**#@-*/


if (!isset($GLOBALS['RODSConnManager']))
    $GLOBALS['RODSConnManager'] = new RODSConnManager();


class RODSConnManager
{
    private $waiting_queue;
    private $conn_map;

    public function __construct()
    {
        $this->waiting_queue = array();
        $this->conn_map = array();
    }

    public static function getConn(RODSAccount $account)
    {
        $manager = $GLOBALS['RODSConnManager'];

        $conn = new RODSConn($account);
        $conn_sig = $conn->getSignature();
        if (!isset($manager->conn_map[$conn_sig]))
            $manager->conn_map[$conn_sig] = array();

        //check if there is any opened connection idle
        foreach ($manager->conn_map[$conn_sig] as &$opened_conn) {
            if ($opened_conn->isIdle()) {
                //$opened_conn->lock();
                $account = $opened_conn->getAccount(); //update account if needed...
                return $opened_conn;
            }
        }

        //check if there is any more new connection allowed
        if (count($manager->conn_map[$conn_sig]) < MAX_NUM_CONN_PER_USER_SERVER) {
            $conn->connect();
            $id = count($manager->conn_map[$conn_sig]);
            $manager->conn_map[$conn_sig][$id] = $conn;
            $conn->setId($id);
            //$conn->lock();
            $account = $conn->getAccount(); //update account if needed...
            return $conn;
        }

        //because PHP doesn't support multithread, if we run out of connections,
        //there is probably something went wrong.
        throw new RODSException("Unexpectedly ran out of connections. Maybe some connections are not released??? ",
            "PERR_INTERNAL_ERR");

        //if no connection are available, sleep for 100ms and retry
        usleep(100);
        echo "i am sleeping... <br/> \n";
        return RODSConnManager::getConn($account);
    }

    public static function releaseConn(RODSConn $conn)
    {
        $manager = $GLOBALS['RODSConnManager'];
        $conn_sig = $conn->getSignature();

        //echo "id:".$conn->getId()." ".implode(",",array_keys($manager->conn_map[$conn_sig]))."<br/>\n";

        if (isset($manager->conn_map[$conn_sig][$conn->getId()])) {
            $manager->conn_map[$conn_sig][$conn->getId()]->unlock();
        }
    }
}
