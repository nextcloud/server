<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use Exception;
use Nextcloud\LogNormalizer\Normalizer;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Log\ExceptionSerializer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\IUserSession;
use OCP\Log\BeforeMessageLoggedEvent;
use OCP\Log\IDataLogger;
use OCP\Log\IFileBased;
use OCP\Log\IWriter;
use OCP\Support\CrashReport\IRegistry;
use Throwable;
use function array_merge;
use function strtr;

/**
 * logging utilities
 *
 * This is a stand in, this should be replaced by a Psr\Log\LoggerInterface
 * compatible logger. See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 *
 * MonoLog is an example implementing this interface.
 */
class Log implements ILogger, IDataLogger {
	private IWriter $logger;
	private ?SystemConfig $config;
	private ?bool $logConditionSatisfied = null;
	private ?Normalizer $normalizer;
	private ?IRegistry $crashReporters;
	private ?IEventDispatcher $eventDispatcher;

	/**
	 * @param IWriter $logger The logger that should be used
	 * @param SystemConfig $config the system config object
	 * @param Normalizer|null $normalizer
	 * @param IRegistry|null $registry
	 */
	public function __construct(
		IWriter $logger,
		SystemConfig $config = null,
		Normalizer $normalizer = null,
		IRegistry $registry = null
	) {
		// FIXME: Add this for backwards compatibility, should be fixed at some point probably
		if ($config === null) {
			$config = \OC::$server->getSystemConfig();
		}

		$this->config = $config;
		$this->logger = $logger;
		if ($normalizer === null) {
			$this->normalizer = new Normalizer();
		} else {
			$this->normalizer = $normalizer;
		}
		$this->crashReporters = $registry;
		$this->eventDispatcher = null;
	}

	public function setEventDispatcher(IEventDispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function emergency(string $message, array $context = []) {
		$this->log(ILogger::FATAL, $message, $context);
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
	public function alert(string $message, array $context = []) {
		$this->log(ILogger::ERROR, $message, $context);
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
	public function critical(string $message, array $context = []) {
		$this->log(ILogger::ERROR, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function error(string $message, array $context = []) {
		$this->log(ILogger::ERROR, $message, $context);
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
	public function warning(string $message, array $context = []) {
		$this->log(ILogger::WARN, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function notice(string $message, array $context = []) {
		$this->log(ILogger::INFO, $message, $context);
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
	public function info(string $message, array $context = []) {
		$this->log(ILogger::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function debug(string $message, array $context = []) {
		$this->log(ILogger::DEBUG, $message, $context);
	}


	/**
	 * Logs with an arbitrary level.
	 *
	 * @param int $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log(int $level, string $message, array $context = []) {
		$minLevel = $this->getLogLevel($context);

		array_walk($context, [$this->normalizer, 'format']);

		$app = $context['app'] ?? 'no app in context';
		$entry = $this->interpolateMessage($context, $message);

		if ($this->eventDispatcher) {
			$this->eventDispatcher->dispatchTyped(new BeforeMessageLoggedEvent($app, $level, $entry));
		}

		$hasBacktrace = isset($entry['exception']);
		$logBacktrace = $this->config->getValue('log.backtrace', false);
		if (!$hasBacktrace && $logBacktrace) {
			$entry['backtrace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}

		try {
			if ($level >= $minLevel) {
				$this->writeLog($app, $entry, $level);

				if ($this->crashReporters !== null) {
					$messageContext = array_merge(
						$context,
						[
							'level' => $level
						]
					);
					$this->crashReporters->delegateMessage($entry['message'], $messageContext);
				}
			} else {
				if ($this->crashReporters !== null) {
					$this->crashReporters->delegateBreadcrumb($entry['message'], 'log', $context);
				}
			}
		} catch (Throwable $e) {
			// make sure we dont hard crash if logging fails
		}
	}

	public function getLogLevel($context) {
		$logCondition = $this->config->getValue('log.condition', []);

		/**
		 * check for a special log condition - this enables an increased log on
		 * a per request/user base
		 */
		if ($this->logConditionSatisfied === null) {
			// default to false to just process this once per request
			$this->logConditionSatisfied = false;
			if (!empty($logCondition)) {
				// check for secret token in the request
				if (isset($logCondition['shared_secret'])) {
					$request = \OC::$server->getRequest();

					if ($request->getMethod() === 'PUT' &&
						!str_contains($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded') &&
						!str_contains($request->getHeader('Content-Type'), 'application/json')) {
						$logSecretRequest = '';
					} else {
						$logSecretRequest = $request->getParam('log_secret', '');
					}

					// if token is found in the request change set the log condition to satisfied
					if ($request && hash_equals($logCondition['shared_secret'], $logSecretRequest)) {
						$this->logConditionSatisfied = true;
					}
				}

				// check for user
				if (isset($logCondition['users'])) {
					$user = \OCP\Server::get(IUserSession::class)->getUser();

					if ($user === null) {
						// User is not known for this request yet
						$this->logConditionSatisfied = null;
					} elseif (in_array($user->getUID(), $logCondition['users'], true)) {
						// if the user matches set the log condition to satisfied
						$this->logConditionSatisfied = true;
					}
				}
			}
		}

		// if log condition is satisfied change the required log level to DEBUG
		if ($this->logConditionSatisfied) {
			return ILogger::DEBUG;
		}

		if (isset($context['app'])) {
			$app = $context['app'];

			/**
			 * check log condition based on the context of each log message
			 * once this is met -> change the required log level to debug
			 */
			if (!empty($logCondition)
				&& isset($logCondition['apps'])
				&& in_array($app, $logCondition['apps'], true)) {
				return ILogger::DEBUG;
			}
		}

		return min($this->config->getValue('loglevel', ILogger::WARN), ILogger::FATAL);
	}

	/**
	 * Logs an exception very detailed
	 *
	 * @param Exception|Throwable $exception
	 * @param array $context
	 * @return void
	 * @since 8.2.0
	 */
	public function logException(Throwable $exception, array $context = []) {
		$app = $context['app'] ?? 'no app in context';
		$level = $context['level'] ?? ILogger::ERROR;

		$minLevel = $this->getLogLevel($context);
		if ($level < $minLevel && ($this->crashReporters === null || !$this->crashReporters->hasReporters())) {
			return;
		}

		// if an error is raised before the autoloader is properly setup, we can't serialize exceptions
		try {
			$serializer = $this->getSerializer();
		} catch (Throwable $e) {
			$this->error("Failed to load ExceptionSerializer serializer while trying to log " . $exception->getMessage());
			return;
		}
		$data = $context;
		unset($data['app']);
		unset($data['level']);
		$data = array_merge($serializer->serializeException($exception), $data);
		$data = $this->interpolateMessage($data, isset($context['message']) && $context['message'] !== '' ? $context['message'] : ('Exception thrown: ' . get_class($exception)), 'CustomMessage');


		array_walk($context, [$this->normalizer, 'format']);

		if ($this->eventDispatcher) {
			$this->eventDispatcher->dispatchTyped(new BeforeMessageLoggedEvent($app, $level, $data));
		}

		try {
			if ($level >= $minLevel) {
				if (!$this->logger instanceof IFileBased) {
					$data = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);
				}
				$this->writeLog($app, $data, $level);
			}

			$context['level'] = $level;
			if (!is_null($this->crashReporters)) {
				$this->crashReporters->delegateReport($exception, $context);
			}
		} catch (Throwable $e) {
			// make sure we dont hard crash if logging fails
		}
	}

	public function logData(string $message, array $data, array $context = []): void {
		$app = $context['app'] ?? 'no app in context';
		$level = $context['level'] ?? ILogger::ERROR;

		$minLevel = $this->getLogLevel($context);

		array_walk($context, [$this->normalizer, 'format']);

		try {
			if ($level >= $minLevel) {
				$data['message'] = $message;
				if (!$this->logger instanceof IFileBased) {
					$data = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);
				}
				$this->writeLog($app, $data, $level);
			}

			$context['level'] = $level;
		} catch (Throwable $e) {
			// make sure we dont hard crash if logging fails
			error_log('Error when trying to log exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
		}
	}

	/**
	 * @param string $app
	 * @param string|array $entry
	 * @param int $level
	 */
	protected function writeLog(string $app, $entry, int $level) {
		$this->logger->write($app, $entry, $level);
	}

	public function getLogPath():string {
		if ($this->logger instanceof IFileBased) {
			return $this->logger->getLogFilePath();
		}
		throw new \RuntimeException('Log implementation has no path');
	}

	/**
	 * Interpolate $message as defined in PSR-3
	 *
	 * Returns an array containing the context without the interpolated
	 * parameters placeholders and the message as the 'message' - or
	 * user-defined - key.
	 */
	private function interpolateMessage(array $context, string $message, string $messageKey = 'message'): array {
		$replace = [];
		$usedContextKeys = [];
		foreach ($context as $key => $val) {
			$fullKey = '{' . $key . '}';
			$replace[$fullKey] = $val;
			if (str_contains($message, $fullKey)) {
				$usedContextKeys[$key] = true;
			}
		}
		return array_merge(array_diff_key($context, $usedContextKeys), [$messageKey => strtr($message, $replace)]);
	}

	/**
	 * @throws Throwable
	 */
	protected function getSerializer(): ExceptionSerializer {
		$serializer = new ExceptionSerializer($this->config);
		try {
			/** @var Coordinator $coordinator */
			$coordinator = \OCP\Server::get(Coordinator::class);
			foreach ($coordinator->getRegistrationContext()->getSensitiveMethods() as $registration) {
				$serializer->enlistSensitiveMethods($registration->getName(), $registration->getValue());
			}
			// For not every app might be initialized at this time, we cannot assume that the return value
			// of getSensitiveMethods() is complete. Running delegates in Coordinator::registerApps() is
			// not possible due to dependencies on the one hand. On the other it would work only with
			// adding public methods to the PsrLoggerAdapter and this class.
			// Thus, serializer cannot be a property.
		} catch (Throwable $t) {
			// ignore app-defined sensitive methods in this case - they weren't loaded anyway
		}
		return $serializer;
	}
}
