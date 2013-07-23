<?php
/**
 * Developer: Daniel
 * Date: 11.01.13
 * Time: 16:45
 */

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_ticketAdminInp extends RODSPacket
{
    public function __construct($arg1 = 'session', // session -> to use it, create -> to... create!,
                                $arg2 = '', // the actual ticket
                                $arg3 = '', // "read" or "write" -> in case of "create" above
                                $arg4 = '', // full path to the resource, e.g.: /tempZone/home/rods/as
                                $arg5 = '',
                                $arg6 = '')
    {

        $packlets = array(  'arg1' => $arg1,
                            'arg2' => $arg2,
                            'arg3' => $arg3,
                            'arg4' => $arg4,
                            'arg5' => $arg5,
                            'arg6' => $arg6,
                        );
        parent::__construct('ticketAdminInp_PI', $packlets);
    }

}