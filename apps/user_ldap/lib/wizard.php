<?php

/**
 * ownCloud – LDAP Wizard
 *
 * @author Arthur Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib;

class Wizard extends LDAPUtility {
	static protected $l;
	protected $configuration;
	protected $result;

	/**
	 * @brief Constructor
	 * @param $configuration an instance of Configuration
	 * @param $ldap	an instance of ILDAPWrapper
	 */
	public function __construct(Configuration $configuration, ILDAPWrapper $ldap) {
		parent::__construct($ldap);
		$this->configuration = $configuration;
		if(is_null(Wizard::$l)) {
			Wizard::$l = \OC_L10N::get('user_ldap');
		}
		$this->result = new WizardResult;
	}

	public function  __destruct() {
		if($this->result->hasChanges()) {
			$this->configuration->saveConfiguration();
		}
	}

	/**
	 * Tries to determine the port, requires given Host, User DN and Password
	 * @returns mixed WizardResult on success, false otherwise
	 */
	public function guessPortAndTLS() {
		if(!$this->checkRequirements(array('ldapHost',
										   'ldapAgentName',
										   'ldapAgentPassword'))) {
			return false;
		}
		$this->checkHost();
		$portSettings = $this->getPortSettingsToTry();
		file_put_contents('/tmp/ps', print_r($portSettings, true).PHP_EOL, FILE_APPEND);

		if(!is_array($portSettings)) {
			throw new \Exception(print_r($portSettings, true));
		}

		//proceed from the best configuration and return on first success
		foreach($portSettings as $setting) {
			$p = $setting['port'];
			$t = $setting['tls'];
			\OCP\Util::writeLog('user_ldap', 'Wiz: trying port '. $p . ', TLS '. $t, \OCP\Util::DEBUG);
			//connectAndBind may throw Exception, it needs to be catched by the
			//callee of this method
			if($this->connectAndBind($p, $t) === true) {
				$config = array('ldapPort' => $p,
								'ldapTLS'  => intval($t)
							);
				$this->configuration->setConfiguration($config);
				\OCP\Util::writeLog('user_ldap', 'Wiz: detected Port '. $p, \OCP\Util::DEBUG);
				$this->result->addChange('ldap_port', $p);
				$this->result->addChange('ldap_tls', intval($t));
// 				$this->result->addSpecific('port', $p);
				return $this->result;
			}
		}

		//custom port, undetected (we do not brute force)
		return false;
	}

	public function guessBaseDN() {
		if(!$this->checkRequirements(array('ldapHost',
										   'ldapAgentName',
										   'ldapAgentPassword',
										   'ldapPort',
										   ))) {
			return false;
		}
		$cr = $this->getConnection();
		if(!$cr) {
			return false;
		}
	}

	/**
	 * @brief Checks, whether a port was entered in the Host configuration
	 * field. In this case the port will be stripped off, but also stored as
	 * setting.
	 */
	private function checkHost() {
		$host = $this->configuration->ldapHost;
		$hostInfo = parse_url($host);

		//removes Port from Host
		if(is_array($hostInfo) && isset($hostInfo['port'])) {
			$port = $hostInfo['port'];
			$host = str_replace(':'.$port, '', $host);
			$config = array('ldapHost' => $host,
							'ldapPort' => $port,
							);
			$this->result->addChange('ldap_host', $host);
			$this->result->addChange('ldap_port', $port);
			$this->configuration->setConfiguration($config);
		}
	}

	/**
	 * Connects and Binds to an LDAP Server
	 * @param $port the port to connect with
	 * @param $tls whether startTLS is to be used
	 * @return
	 */
	private function connectAndBind($port = 389, $tls = false, $ncc = false) {
		if($ncc) {
			//No certificate check
			//FIXME: undo afterwards
			putenv('LDAPTLS_REQCERT=never');
		}

		//connect, does not really trigger any server communication
		\OCP\Util::writeLog('user_ldap', 'Wiz: Checking Host Info ', \OCP\Util::DEBUG);
		$host = $this->configuration->ldapHost;
		$hostInfo = parse_url($host);
		if(!$hostInfo) {
			throw new \Exception($this->l->t('Invalid Host'));
		}
		if(isset($hostInfo['scheme'])) {
			if(isset($hostInfo['port'])) {
				//problem
			} else {
				$host .= ':' . $port;
			}
		}
		\OCP\Util::writeLog('user_ldap', 'Wiz: Attempting to connect ', \OCP\Util::DEBUG);
		$cr = $this->ldap->connect($host, $port);
		if(!is_resource($cr)) {
			throw new \Exception($this->l->t('Invalid Host'));
		}

		\OCP\Util::writeLog('user_ldap', 'Wiz: Setting LDAP Options ', \OCP\Util::DEBUG);
		//set LDAP options
		if($this->ldap->setOption($cr, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			if($tls) {
				$this->ldap->startTls($cr);
			}
		}

		\OCP\Util::writeLog('user_ldap', 'Wiz: Attemping to Bind ', \OCP\Util::DEBUG);
		//interesting part: do the bind!
		$login = $this->ldap->bind($cr,
									$this->configuration->ldapAgentName,
									$this->configuration->ldapAgentPassword);

		if($login === true) {
			$this->ldap->unbind($cr);
			if($ncc) {
				throw new \Exception('Certificate cannot be validated.');
			}
			\OCP\Util::writeLog('user_ldap', 'Wiz: Bind succesfull with Port '. $port, \OCP\Util::DEBUG);
			return true;
		}

		$errno = $this->ldap->errno($cr);
		$error = ldap_error($cr);
		$this->ldap->unbind($cr);
		if($errno === -1 || ($errno === 2 && $ncc)) {
			//host, port or TLS wrong
			return false;
		} else if ($errno === 2) {
			return $this->connectAndBind($port, $tls, true);
		}
		throw new \Exception($error);
	}

	private function checkRequirements($reqs) {
		foreach($reqs as $option) {
			$value = $this->configuration->$option;
			if(empty($value)) {
				return false;
			}
		}
		return true;
	}

	private function getConnection() {
		$cr = $this->ldap->connect(
			$this->configuration->ldapHost.':'.$this->configuration->ldapPort,
			$this->configuration->ldapPort);

		if($this->ldap->setOption($cr, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			if($this->configuration->ldapTLS === 1) {
				$this->ldap->startTls($cr);
			}
		}

		$lo = @$this->ldap->bind($cr,
								 $this->configuration->ldapAgentName,
								 $this->configuration->ldapAgentPassword);
		if($lo === true) {
			return $cr;
		}

		return false;
	}

	private function getDefaultLdapPortSettings() {
		static $settings = array(
								array('port' => 7636, 'tls' => false),
								array('port' =>  636, 'tls' => false),
								array('port' => 7389, 'tls' => true),
								array('port' =>  389, 'tls' => true),
								array('port' => 7389, 'tls' => false),
								array('port' =>  389, 'tls' => false),
						  );
		return $settings;
	}

	private function getPortSettingsToTry() {
		//389 ← LDAP / Unencrypted or StartTLS
		//636 ← LDAPS / SSL
		//7xxx ← UCS. need to be checked first, because both ports may be open
		$host = $this->configuration->ldapHost;
		$port = intval($this->configuration->ldapPort);
		$portSettings = array();

		//In case the port is already provided, we will check this first
		if($port > 0) {
			$hostInfo = parse_url($host);
			if(is_array($hostInfo)
				&& isset($hostInfo['scheme'])
				&& stripos($hostInfo['scheme'], 'ldaps') === false) {
				$portSettings[] = array('port' => $port, 'tls' => true);
			}
			$portSettings[] =array('port' => $port, 'tls' => false);
		}

		//default ports
		$portSettings = array_merge($portSettings,
		                            $this->getDefaultLdapPortSettings());

		return $portSettings;
	}


}