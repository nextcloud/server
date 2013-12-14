<?php

/**
 * ProdsRule class. Provides iRODS rule related functionalities.
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package Prods
 */
require_once("autoload.inc.php");

class ProdsRule
{
    public $account;
    public $body;
    public $inp_params;
    public $out_params;
    public $remotesvr;
    public $options;

    /*
     * @param RODSAccount account this is the account used to connect to iRODS server
     * @param string $rule_body body of the rule. Read this tutorial for details about rules: http://www.irods.org/index.php/Executing_user_defined_rules/workflow
     * @param array $inp_params associative array defining input parameters for micro services used in this rule. only string and keyval pair are supported at this time. If the array value is a string, then type is string, if the array value is an RODSKeyValPair object, it will be treated a keyval pair
     * @param array $out_params an array of names (strings)
     * @param array $remotesvr if this rule need to run at remote server, this associative array should have the following keys:
     *    - 'host' remote host name or address
     *    - 'port' remote port
     *    - 'zone' remote zone
     *    if any of the value is empty, this option will be ignored.
     * @param RODSKeyValPair $options an RODSKeyValPair specifying additional options, purpose of this is unknown at the developement time. Leave it alone if you are as clueless as me...
     */
    public function __construct(RODSAccount $account, $rule_body,
                                array $inp_params = array(), array $out_params = array(),
                                array $remotesvr = array(), RODSKeyValPair $options = null)
    {
        $this->account = $account;
        $this->rule_body = $rule_body;
        $this->inp_params = $inp_params;
        $this->out_params = $out_params;
        $this->remotesvr = $remotesvr;
        if (isset($options))
            $this->options = $options;
        else
            $this->options = new RODSKeyValPair();
    }

    /**
     * Excute the rule, assign
     * @return an associative array. Each array key is the lable, and each array value's type will depend on the type of $out_param, at this moment, only string and RODSKeyValPair are supported
     */
    public function execute()
    {
        $conn = RODSConnManager::getConn($this->account);
        $result = $conn->execUserRule($this->rule_body, $this->inp_params,
            $this->out_params, $this->remotesvr, $this->options = null);
        RODSConnManager::releaseConn($conn);

        return $result;
    }
}
