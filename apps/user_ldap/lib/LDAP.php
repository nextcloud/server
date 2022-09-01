<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Alexander Bergolth <leo@strike.wu.ac.at>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Peter Kubica <peter@kubica.ch>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_LDAP;

use OCP\Profiler\IProfiler;
use OC\ServerNotAvailableException;
use OCA\User_LDAP\DataCollector\LdapDataCollector;
use OCA\User_LDAP\Exceptions\ConstraintViolationException;
use OCA\User_LDAP\PagedResults\IAdapter;
use OCA\User_LDAP\PagedResults\Php73;

class LDAP implements ILDAPWrapper {
	protected $logFile = '';
	protected $curFunc = '';
	protected $curArgs = [];

	/** @var IAdapter */
	protected $pagedResultsAdapter;

	private ?LdapDataCollector $dataCollector = null;

	public function __construct(string $logFile = '') {
		$this->pagedResultsAdapter = new Php73();
		$this->logFile = $logFile;

		/** @var IProfiler $profiler */
		$profiler = \OC::$server->get(IProfiler::class);
		if ($profiler->isEnabled()) {
			$this->dataCollector = new LdapDataCollector();
			$profiler->add($this->dataCollector);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function bind($link, $dn, $password) {
		return $this->invokeLDAPMethod('bind', $link, $dn, $password);
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect($host, $port) {
		if (strpos($host, '://') === false) {
			$host = 'ldap://' . $host;
		}
		if (strpos($host, ':', strpos($host, '://') + 1) === false) {
			//ldap_connect ignores port parameter when URLs are passed
			$host .= ':' . $port;
		}
		return $this->invokeLDAPMethod('connect', $host);
	}

	/**
	 * {@inheritDoc}
	 */
	public function controlPagedResultResponse($link, $result, &$cookie): bool {
		$this->preFunctionCall(
			$this->pagedResultsAdapter->getResponseCallFunc(),
			$this->pagedResultsAdapter->getResponseCallArgs([$link, $result, &$cookie])
		);

		$result = $this->pagedResultsAdapter->responseCall($link);
		$cookie = $this->pagedResultsAdapter->getCookie($link);

		if ($this->isResultFalse($result)) {
			$this->postFunctionCall();
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function controlPagedResult($link, $pageSize, $isCritical): void {
		$this->pagedResultsAdapter->setRequestParameters($link, $pageSize, $isCritical);
	}

	/**
	 * {@inheritDoc}
	 */
	public function countEntries($link, $result) {
		return $this->invokeLDAPMethod('count_entries', $link, $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function errno($link) {
		return $this->invokeLDAPMethod('errno', $link);
	}

	/**
	 * {@inheritDoc}
	 */
	public function error($link) {
		return $this->invokeLDAPMethod('error', $link);
	}

	/**
	 * Splits DN into its component parts
	 * @param string $dn
	 * @param int @withAttrib
	 * @return array|false
	 * @link https://www.php.net/manual/en/function.ldap-explode-dn.php
	 */
	public function explodeDN($dn, $withAttrib) {
		return $this->invokeLDAPMethod('explode_dn', $dn, $withAttrib);
	}

	/**
	 * {@inheritDoc}
	 */
	public function firstEntry($link, $result) {
		return $this->invokeLDAPMethod('first_entry', $link, $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttributes($link, $result) {
		return $this->invokeLDAPMethod('get_attributes', $link, $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDN($link, $result) {
		return $this->invokeLDAPMethod('get_dn', $link, $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEntries($link, $result) {
		return $this->invokeLDAPMethod('get_entries', $link, $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function nextEntry($link, $result) {
		return $this->invokeLDAPMethod('next_entry', $link, $result);
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($link, $baseDN, $filter, $attr) {
		return $this->invokeLDAPMethod('read', $link, $baseDN, $filter, $attr, 0, -1);
	}

	/**
	 * {@inheritDoc}
	 */
	public function search($link, $baseDN, $filter, $attr, $attrsOnly = 0, $limit = 0) {
		$oldHandler = set_error_handler(function ($no, $message, $file, $line) use (&$oldHandler) {
			if (strpos($message, 'Partial search results returned: Sizelimit exceeded') !== false) {
				return true;
			}
			$oldHandler($no, $message, $file, $line);
			return true;
		});
		try {
			$this->pagedResultsAdapter->setSearchArgs($link, $baseDN, $filter, $attr, $attrsOnly, $limit);
			$result = $this->invokeLDAPMethod('search', ...$this->pagedResultsAdapter->getSearchArgs($link));

			restore_error_handler();
			return $result;
		} catch (\Exception $e) {
			restore_error_handler();
			throw $e;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function modReplace($link, $userDN, $password) {
		return $this->invokeLDAPMethod('mod_replace', $link, $userDN, ['userPassword' => $password]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function exopPasswd($link, $userDN, $oldPassword, $password) {
		return $this->invokeLDAPMethod('exop_passwd', $link, $userDN, $oldPassword, $password);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setOption($link, $option, $value) {
		return $this->invokeLDAPMethod('set_option', $link, $option, $value);
	}

	/**
	 * {@inheritDoc}
	 */
	public function startTls($link) {
		return $this->invokeLDAPMethod('start_tls', $link);
	}

	/**
	 * {@inheritDoc}
	 */
	public function unbind($link) {
		return $this->invokeLDAPMethod('unbind', $link);
	}

	/**
	 * Checks whether the server supports LDAP
	 * @return boolean if it the case, false otherwise
	 * */
	public function areLDAPFunctionsAvailable() {
		return function_exists('ldap_connect');
	}

	/**
	 * {@inheritDoc}
	 */
	public function isResource($resource) {
		return is_resource($resource) || is_object($resource);
	}

	/**
	 * Checks whether the return value from LDAP is wrong or not.
	 *
	 * When using ldap_search we provide an array, in case multiple bases are
	 * configured. Thus, we need to check the array elements.
	 *
	 * @param $result
	 * @return bool
	 */
	protected function isResultFalse($result) {
		if ($result === false) {
			return true;
		}

		if ($this->curFunc === 'ldap_search' && is_array($result)) {
			foreach ($result as $singleResult) {
				if ($singleResult === false) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	protected function invokeLDAPMethod() {
		$arguments = func_get_args();
		$func = 'ldap_' . array_shift($arguments);
		if (function_exists($func)) {
			$this->preFunctionCall($func, $arguments);
			$result = call_user_func_array($func, $arguments);
			if ($this->isResultFalse($result)) {
				$this->postFunctionCall();
			}
			if ($this->dataCollector !== null) {
				$this->dataCollector->stopLastLdapRequest();
			}
			return $result;
		}
		return null;
	}

	private function preFunctionCall(string $functionName, array $args): void {
		$this->curFunc = $functionName;
		$this->curArgs = $args;

		if ($this->dataCollector !== null) {
			$args = array_map(function ($item) {
				if ($this->isResource($item)) {
					return '(resource)';
				}
				if (isset($item[0]['value']['cookie']) && $item[0]['value']['cookie'] !== "") {
					$item[0]['value']['cookie'] = "*opaque cookie*";
				}
				return $item;
			}, $this->curArgs);

			$this->dataCollector->startLdapRequest($this->curFunc, $args);
		}

		if ($this->logFile !== '' && is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
			$args = array_map(fn ($item) => (!$this->isResource($item) ? $item : '(resource)'), $this->curArgs);
			file_put_contents(
				$this->logFile,
				$this->curFunc . '::' . json_encode($args) . "\n",
				FILE_APPEND
			);
		}
	}

	/**
	 * Analyzes the returned LDAP error and acts accordingly if not 0
	 *
	 * @param resource|\LDAP\Connection $resource the LDAP Connection resource
	 * @throws ConstraintViolationException
	 * @throws ServerNotAvailableException
	 * @throws \Exception
	 */
	private function processLDAPError($resource) {
		$errorCode = ldap_errno($resource);
		if ($errorCode === 0) {
			return;
		}
		$errorMsg = ldap_error($resource);

		if ($this->curFunc === 'ldap_get_entries'
			&& $errorCode === -4) {
		} elseif ($errorCode === 32) {
			//for now
		} elseif ($errorCode === 10) {
			//referrals, we switch them off, but then there is AD :)
		} elseif ($errorCode === -1) {
			throw new ServerNotAvailableException('Lost connection to LDAP server.');
		} elseif ($errorCode === 52) {
			throw new ServerNotAvailableException('LDAP server is shutting down.');
		} elseif ($errorCode === 48) {
			throw new \Exception('LDAP authentication method rejected', $errorCode);
		} elseif ($errorCode === 1) {
			throw new \Exception('LDAP Operations error', $errorCode);
		} elseif ($errorCode === 19) {
			ldap_get_option($this->curArgs[0], LDAP_OPT_ERROR_STRING, $extended_error);
			throw new ConstraintViolationException(!empty($extended_error)?$extended_error:$errorMsg, $errorCode);
		} else {
			\OC::$server->getLogger()->debug('LDAP error {message} ({code}) after calling {func}', [
				'app' => 'user_ldap',
				'message' => $errorMsg,
				'code' => $errorCode,
				'func' => $this->curFunc,
			]);
		}
	}

	/**
	 * Called after an ldap method is run to act on LDAP error if necessary
	 * @throw \Exception
	 */
	private function postFunctionCall() {
		if ($this->isResource($this->curArgs[0])) {
			$resource = $this->curArgs[0];
		} elseif (
			   $this->curFunc === 'ldap_search'
			&& is_array($this->curArgs[0])
			&& $this->isResource($this->curArgs[0][0])
		) {
			// we use always the same LDAP connection resource, is enough to
			// take the first one.
			$resource = $this->curArgs[0][0];
		} else {
			return;
		}

		$this->processLDAPError($resource);

		$this->curFunc = '';
		$this->curArgs = [];
	}
}
