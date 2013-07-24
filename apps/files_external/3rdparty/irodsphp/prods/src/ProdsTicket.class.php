<?php
/**
 * Developer: Daniel Speichert <s@drexel.edu>
 * Date: 30.01.13
 * Time: 14:15
 */

require_once("autoload.inc.php");

class ProdsTicket
{
    private $account;

    public function __construct( RODSAccount &$account )
    {
        $this->account = $account;
    }

    /*
     * This is just a stupid wrapper
     * It proxifies RODSConn->createTicket
     */
    public function createTicket( $object, $permission = 'read', $ticket = '' )
    {
        $conn = RODSConnManager::getConn($this->account);
        $ticket = $conn->createTicket($object, $permission, $ticket );
        RODSConnManager::releaseConn($conn);
        return $ticket;
    }

    /*
     * This is also a stupid wrapper
     * It proxifies RODSConn->deleteTicket
     */
    public function deleteTicket( $ticket )
    {
        $conn = RODSConnManager::getConn($this->account);
        $ticket = $conn->deleteTicket( $ticket );
        RODSConnManager::releaseConn($conn);
    }
}