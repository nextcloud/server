<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

use InterfaSys\LogNormalizer\Normalizer;

use \OCP\ILogger;
use OCP\Security\StringUtils;
use OCP\Util;

/**
 * logging utilities
 *
 * This is a stand in, this should be replaced by a Psr\Log\LoggerInterface
 * compatible logger. See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 *
 * MonoLog is an example implementing this interface.
 */

class Log implements ILogger {

	/** @var string */
	private $logger;

	/** @var SystemConfig */
	private $config;

	/** @var boolean|null cache the result of the log condition check for the request */
	private $logConditionSatisfied = null;

	/** @var Normalizer */
	private $normalizer;

	protected $methodsWithSensitiveParameters = [
		// Session/User
		'login',
		'checkPassword',
		'loginWithPassword',
		'updatePrivateKeyPassword',
		'validateUserPass',

		// TokenProvider
		'getToken',
		'isTokenPassword',
		'getPassword',
		'decryptPassword',
		'logClientIn',
		'generateToken',
		'validateToken',

		// TwoFactorAuth
		'solveChallenge',
		'verifyChallenge',

		//ICrypto
		'calculateHMAC',
		'encrypt',
		'decrypt',

		//LoginController
		'tryLogin'
	];

	/**
	 * @param string $logger The logger that should be used
	 * @param SystemConfig $config the system config object
	 * @param null $normalizer
	 */
	public function __construct($logger=null, SystemConfig $config=null, $normalizer = null) {
		// FIXME: Add this for backwards compatibility, should be fixed at some point probably
		if($config === null) {
			$config = \OC::$server->getSystemConfig();
		}

		$this->config = $config;

		// FIXME: Add this for backwards compatibility, should be fixed at some point probably
		if($logger === null) {
			$this->logger = 'OC\\Log\\'.ucfirst($this->config->getValue('log_type', 'owncloud'));
			call_user_func(array($this->logger, 'init'));
		} else {
			$this->logger = $logger;
		}
		if ($normalizer === null) {
			$this->normalizer = new Normalizer();
		} else {
			$this->normalizer = $normalizer;
		}

	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function emergency($message, array $context = array()) {
		$this->log(Util::FATAL, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function alert($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function critical($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function error($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function warning($message, array $context = array()) {
		$this->log(Util::WARN, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function notice($message, array $context = array()) {
		$this->log(Util::INFO, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function info($message, array $context = array()) {
		$this->log(Util::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function debug($message, array $context = array()) {
		$this->log(Util::DEBUG, $message, $context);
	}


	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = array()) {
		$minLevel = min($this->config->getValue('loglevel', Util::WARN), Util::FATAL);
		$logCondition = $this->config->getValue('log.condition', []);

		array_walk($context, [$this->normalizer, 'format']);

		if (isset($context['app'])) {
			$app = $context['app'];

			/**
			 * check log condition based on the context of each log message
			 * once this is met -> change the required log level to debug
			 */
			if(!empty($logCondition)
				&& isset($logCondition['apps'])
				&& in_array($app, $logCondition['apps'], true)) {
				$minLevel = Util::DEBUG;
			}

		} else {
			$app = 'no app in context';
		}
		// interpolate $message as defined in PSR-3
		$replace = array();
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		// interpolate replacement values into the message and return
		$message = strtr($message, $replace);

		/**
		 * check for a special log condition - this enables an increased log on
		 * a per request/user base
		 */
		if($this->logConditionSatisfied === null) {
			// default to false to just process this once per request
			$this->logConditionSatisfied = false;
			if(!empty($logCondition)) {

				// check for secret token in the request
				if(isset($logCondition['shared_secret'])) {
					$request = \OC::$server->getRequest();

					// if token is found in the request change set the log condition to satisfied
					if($request && hash_equals($logCondition['shared_secret'], $request->getParam('log_secret', ''))) {
						$this->logConditionSatisfied = true;
					}
				}

				// check for user
				if(isset($logCondition['users'])) {
					$user = \OC::$server->getUserSession()->getUser();

					// if the user matches set the log condition to satisfied
					if($user !== null && in_array($user->getUID(), $logCondition['users'], true)) {
						$this->logConditionSatisfied = true;
					}
				}
			}
		}

		// if log condition is satisfied change the required log level to DEBUG
		if($this->logConditionSatisfied) {
			$minLevel = Util::DEBUG;
		}

		if ($level >= $minLevel) {
			$logger = $this->logger;
			call_user_func(array($logger, 'write'), $app, $message, $level);
		}
	}

	/**
	 * Logs an exception very detailed
	 *
	 * @param \Exception | \Throwable $exception
	 * @param array $context
	 * @return void
	 * @since 8.2.0
	 */
	public function logException($exception, array $context = array()) {
		$exception = array(
			'Exception' => get_class($exception),
			'Message' => $exception->getMessage(),
			'Code' => $exception->getCode(),
			'Trace' => $exception->getTraceAsString(),
			'File' => $exception->getFile(),
			'Line' => $exception->getLine(),
		);
		$exception['Trace'] = preg_replace('!(' . implode('|', $this->methodsWithSensitiveParameters) . ')\(.*\)!', '$1(*** sensitive parameters replaced ***)', $exception['Trace']);
		$msg = isset($context['message']) ? $context['message'] : 'Exception';
		$msg .= ': ' . json_encode($exception);
		$this->error($msg, $context);
	}
}
