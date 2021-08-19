<?php

/**
 * Pure-PHP ssh-agent client.
 *
 * PHP version 5
 *
 * Here are some examples of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $agent = new \phpseclib\System\SSH\Agent();
 *
 *    $ssh = new \phpseclib\Net\SSH2('www.domain.tld');
 *    if (!$ssh->login('username', $agent)) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $ssh->exec('pwd');
 *    echo $ssh->exec('ls -la');
 * ?>
 * </code>
 *
 * @category  System
 * @package   SSH\Agent
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2014 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 * @internal  See http://api.libssh.org/rfc/PROTOCOL.agent
 */

namespace phpseclib\System\SSH;

use phpseclib\Crypt\RSA;
use phpseclib\System\SSH\Agent\Identity;

/**
 * Pure-PHP ssh-agent client identity factory
 *
 * requestIdentities() method pumps out \phpseclib\System\SSH\Agent\Identity objects
 *
 * @package SSH\Agent
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
class Agent
{
    /**#@+
     * Message numbers
     *
     * @access private
     */
    // to request SSH1 keys you have to use SSH_AGENTC_REQUEST_RSA_IDENTITIES (1)
    const SSH_AGENTC_REQUEST_IDENTITIES = 11;
    // this is the SSH2 response; the SSH1 response is SSH_AGENT_RSA_IDENTITIES_ANSWER (2).
    const SSH_AGENT_IDENTITIES_ANSWER = 12;
    // the SSH1 request is SSH_AGENTC_RSA_CHALLENGE (3)
    const SSH_AGENTC_SIGN_REQUEST = 13;
    // the SSH1 response is SSH_AGENT_RSA_RESPONSE (4)
    const SSH_AGENT_SIGN_RESPONSE = 14;
    /**#@-*/

    /**@+
     * Agent forwarding status
     *
     * @access private
     */
    // no forwarding requested and not active
    const FORWARD_NONE = 0;
    // request agent forwarding when opportune
    const FORWARD_REQUEST = 1;
    // forwarding has been request and is active
    const FORWARD_ACTIVE = 2;
    /**#@-*/

    /**
     * Unused
     */
    const SSH_AGENT_FAILURE = 5;

    /**
     * Socket Resource
     *
     * @var resource
     * @access private
     */
    var $fsock;

    /**
     * Agent forwarding status
     *
     * @access private
     */
    var $forward_status = self::FORWARD_NONE;

    /**
     * Buffer for accumulating forwarded authentication
     * agent data arriving on SSH data channel destined
     * for agent unix socket
     *
     * @access private
     */
    var $socket_buffer = '';

    /**
     * Tracking the number of bytes we are expecting
     * to arrive for the agent socket on the SSH data
     * channel
     */
    var $expected_bytes = 0;

    /**
     * Default Constructor
     *
     * @return \phpseclib\System\SSH\Agent
     * @access public
     */
    function __construct($address = null)
    {
        if (!$address) {
            switch (true) {
                case isset($_SERVER['SSH_AUTH_SOCK']):
                    $address = $_SERVER['SSH_AUTH_SOCK'];
                    break;
                case isset($_ENV['SSH_AUTH_SOCK']):
                    $address = $_ENV['SSH_AUTH_SOCK'];
                    break;
                default:
                    user_error('SSH_AUTH_SOCK not found');
                    return false;
            }
        }

        $this->fsock = fsockopen('unix://' . $address, 0, $errno, $errstr);
        if (!$this->fsock) {
            user_error("Unable to connect to ssh-agent (Error $errno: $errstr)");
        }
    }

    /**
     * Request Identities
     *
     * See "2.5.2 Requesting a list of protocol 2 keys"
     * Returns an array containing zero or more \phpseclib\System\SSH\Agent\Identity objects
     *
     * @return array
     * @access public
     */
    function requestIdentities()
    {
        if (!$this->fsock) {
            return array();
        }

        $packet = pack('NC', 1, self::SSH_AGENTC_REQUEST_IDENTITIES);
        if (strlen($packet) != fputs($this->fsock, $packet)) {
            user_error('Connection closed while requesting identities');
            return array();
        }

        $temp = fread($this->fsock, 4);
        if (strlen($temp) != 4) {
            user_error('Connection closed while requesting identities');
            return array();
        }
        $length = current(unpack('N', $temp));
        $type = ord(fread($this->fsock, 1));
        if ($type != self::SSH_AGENT_IDENTITIES_ANSWER) {
            user_error('Unable to request identities');
            return array();
        }

        $identities = array();
        $temp = fread($this->fsock, 4);
        if (strlen($temp) != 4) {
            user_error('Connection closed while requesting identities');
            return array();
        }
        $keyCount = current(unpack('N', $temp));
        for ($i = 0; $i < $keyCount; $i++) {
            $temp = fread($this->fsock, 4);
            if (strlen($temp) != 4) {
                user_error('Connection closed while requesting identities');
                return array();
            }
            $length = current(unpack('N', $temp));
            $key_blob = fread($this->fsock, $length);
            if (strlen($key_blob) != $length) {
                user_error('Connection closed while requesting identities');
                return array();
            }
            $key_str = 'ssh-rsa ' . base64_encode($key_blob);
            $temp = fread($this->fsock, 4);
            if (strlen($temp) != 4) {
                user_error('Connection closed while requesting identities');
                return array();
            }
            $length = current(unpack('N', $temp));
            if ($length) {
                $temp = fread($this->fsock, $length);
                if (strlen($temp) != $length) {
                    user_error('Connection closed while requesting identities');
                    return array();
                }
                $key_str.= ' ' . $temp;
            }
            $length = current(unpack('N', substr($key_blob, 0, 4)));
            $key_type = substr($key_blob, 4, $length);
            switch ($key_type) {
                case 'ssh-rsa':
                    $key = new RSA();
                    $key->loadKey($key_str);
                    break;
                case 'ssh-dss':
                    // not currently supported
                    break;
            }
            // resources are passed by reference by default
            if (isset($key)) {
                $identity = new Identity($this->fsock);
                $identity->setPublicKey($key);
                $identity->setPublicKeyBlob($key_blob);
                $identities[] = $identity;
                unset($key);
            }
        }

        return $identities;
    }

    /**
     * Signal that agent forwarding should
     * be requested when a channel is opened
     *
     * @param Net_SSH2 $ssh
     * @return bool
     * @access public
     */
    function startSSHForwarding($ssh)
    {
        if ($this->forward_status == self::FORWARD_NONE) {
            $this->forward_status = self::FORWARD_REQUEST;
        }
    }

    /**
     * Request agent forwarding of remote server
     *
     * @param Net_SSH2 $ssh
     * @return bool
     * @access private
     */
    function _request_forwarding($ssh)
    {
        $request_channel = $ssh->_get_open_channel();
        if ($request_channel === false) {
            return false;
        }

        $packet = pack(
            'CNNa*C',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $ssh->server_channels[$request_channel],
            strlen('auth-agent-req@openssh.com'),
            'auth-agent-req@openssh.com',
            1
        );

        $ssh->channel_status[$request_channel] = NET_SSH2_MSG_CHANNEL_REQUEST;

        if (!$ssh->_send_binary_packet($packet)) {
            return false;
        }

        $response = $ssh->_get_channel_packet($request_channel);
        if ($response === false) {
            return false;
        }

        $ssh->channel_status[$request_channel] = NET_SSH2_MSG_CHANNEL_OPEN;
        $this->forward_status = self::FORWARD_ACTIVE;

        return true;
    }

    /**
     * On successful channel open
     *
     * This method is called upon successful channel
     * open to give the SSH Agent an opportunity
     * to take further action. i.e. request agent forwarding
     *
     * @param Net_SSH2 $ssh
     * @access private
     */
    function _on_channel_open($ssh)
    {
        if ($this->forward_status == self::FORWARD_REQUEST) {
            $this->_request_forwarding($ssh);
        }
    }

    /**
     * Forward data to SSH Agent and return data reply
     *
     * @param string $data
     * @return data from SSH Agent
     * @access private
     */
    function _forward_data($data)
    {
        if ($this->expected_bytes > 0) {
            $this->socket_buffer.= $data;
            $this->expected_bytes -= strlen($data);
        } else {
            $agent_data_bytes = current(unpack('N', $data));
            $current_data_bytes = strlen($data);
            $this->socket_buffer = $data;
            if ($current_data_bytes != $agent_data_bytes + 4) {
                $this->expected_bytes = ($agent_data_bytes + 4) - $current_data_bytes;
                return false;
            }
        }

        if (strlen($this->socket_buffer) != fwrite($this->fsock, $this->socket_buffer)) {
            user_error('Connection closed attempting to forward data to SSH agent');
            return false;
        }

        $this->socket_buffer = '';
        $this->expected_bytes = 0;

        $temp = fread($this->fsock, 4);
        if (strlen($temp) != 4) {
            user_error('Connection closed while reading data response');
            return false;
        }
        $agent_reply_bytes = current(unpack('N', $temp));

        $agent_reply_data = fread($this->fsock, $agent_reply_bytes);
        if (strlen($agent_reply_data) != $agent_reply_bytes) {
            user_error('Connection closed while reading data response');
            return false;
        }
        $agent_reply_data = current(unpack('a*', $agent_reply_data));

        return pack('Na*', $agent_reply_bytes, $agent_reply_data);
    }
}
