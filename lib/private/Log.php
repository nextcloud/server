<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use Exception;
use Nextcloud\LogNormalizer\Normalizer;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Log\ExceptionSerializer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Log\BeforeMessageLoggedEvent;
use OCP\Log\IDataLogger;
use OCP\Log\IFileBased;
use OCP\Log\IWriter;
use OCP\Server;
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
	private ?bool $logConditionSatisfied = null;
	private ?IEventDispatcher $eventDispatcher = null;
	private int $nestingLevel = 0;

	public function __construct(
		private IWriter $logger,
		private SystemConfig $config,
		private Normalizer $normalizer = new Normalizer(),
		private ?IRegistry $crashReporters = null,
	) {
	}

	public function setEventDispatcher(IEventDispatcher $eventDispatcher): void {
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function emergency(string $message, array $context = []): void {
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
	 */
	public function alert(string $message, array $context = []): void {
		$this->log(ILogger::ERROR, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function critical(string $message, array $context = []): void {
		$this->log(ILogger::ERROR, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function error(string $message, array $context = []): void {
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
	 */
	public function warning(string $message, array $context = []): void {
		$this->log(ILogger::WARN, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function notice(string $message, array $context = []): void {
		$this->log(ILogger::INFO, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function info(string $message, array $context = []): void {
		$this->log(ILogger::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function debug(string $message, array $context = []): void {
		$this->log(ILogger::DEBUG, $message, $context);
	}


	/**
	 * Logs with an arbitrary level.
	 *
	 * @param int $level
	 * @param string $message
	 * @param array $context
	 */
	public function log(int $level, string $message, array $context = []): void {
		$minLevel = $this->getLogLevel($context, $message);
		if ($level < $minLevel
			&& (($this->crashReporters?->hasReporters() ?? false) === false)
			&& (($this->eventDispatcher?->hasListeners(BeforeMessageLoggedEvent::class) ?? false) === false)) {
			return; // no crash reporter, no listeners, we can stop for lower log level
		}

		$context = array_map($this->normalizer->format(...), $context);

		$app = $context['app'] ?? 'no app in context';
		$entry = $this->interpolateMessage($context, $message);

		$this->eventDispatcher?->dispatchTyped(new BeforeMessageLoggedEvent($app, $level, $entry));

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
				$this->crashReporters?->delegateBreadcrumb($entry['message'], 'log', $context);
			}
		} catch (Throwable $e) {
			// make sure we dont hard crash if logging fails
		}
	}

	public function getLogLevel(array $context, string $message): int {
		if ($this->nestingLevel > 1) {
			return ILogger::WARN;
		}

		$this->nestingLevel++;
		/**
		 * @psalm-var array{
		 *   shared_secret?: string,
		 *   users?: string[],
		 *   apps?: string[],
		 *   matches?: array<array-key, array{
		 *     shared_secret?: string,
		 *     users?: string[],
		 *     apps?: string[],
		 *     message?: string,
		 *     loglevel: 0|1|2|3|4,
		 *   }>
		 * } $logCondition
		 */
		$logCondition = $this->config->getValue('log.condition', []);

		$userId = false;

		/**
		 * check for a special log condition - this enables an increased log on
		 * a per request/user base
		 */
		if ($this->logConditionSatisfied === null) {
			// default to false to just process this once per request
			$this->logConditionSatisfied = false;
			if (!empty($logCondition)) {
				// check for secret token in the request
				if (isset($logCondition['shared_secret']) && $this->checkLogSecret($logCondition['shared_secret'])) {
					$this->logConditionSatisfied = true;
				}

				// check for user
				if (isset($logCondition['users'])) {
					$user = Server::get(IUserSession::class)->getUser();

					if ($user === null) {
						// User is not known for this request yet
						$this->logConditionSatisfied = null;
					} elseif (in_array($user->getUID(), $logCondition['users'], true)) {
						// if the user matches set the log condition to satisfied
						$this->logConditionSatisfied = true;
					} else {
						$userId = $user->getUID();
					}
				}
			}
		}

		// if log condition is satisfied change the required log level to DEBUG
		if ($this->logConditionSatisfied) {
			$this->nestingLevel--;
			return ILogger::DEBUG;
		}

		if ($userId === false && isset($logCondition['matches'])) {
			$user = Server::get(IUserSession::class)->getUser();
			$userId = $user === null ? false : $user->getUID();
		}

		if (isset($context['app'])) {
			/**
			 * check log condition based on the context of each log message
			 * once this is met -> change the required log level to debug
			 */
			if (in_array($context['app'], $logCondition['apps'] ?? [], true)) {
				$this->nestingLevel--;
				return ILogger::DEBUG;
			}
		}

		if (!isset($logCondition['matches'])) {
			$configLogLevel = $this->config->getValue('loglevel', ILogger::WARN);
			if (is_numeric($configLogLevel)) {
				$this->nestingLevel--;
				return min((int)$configLogLevel, ILogger::FATAL);
			}

			// Invalid configuration, warn the user and fall back to default level of WARN
			error_log('Nextcloud configuration: "loglevel" is not a valid integer');
			$this->nestingLevel--;
			return ILogger::WARN;
		}

		foreach ($logCondition['matches'] as $option) {
			if (
				(!isset($option['shared_secret']) || $this->checkLogSecret($option['shared_secret']))
				&& (!isset($option['users']) || in_array($userId, $option['users'], true))
				&& (!isset($option['apps']) || (isset($context['app']) && in_array($context['app'], $option['apps'], true)))
				&& (!isset($option['message']) || str_contains($message, $option['message']))
			) {
				if (!isset($option['apps']) && !isset($option['loglevel']) && !isset($option['message'])) {
					/* Only user and/or secret are listed as conditions, we can cache the result for the rest of the request */
					$this->logConditionSatisfied = true;
					$this->nestingLevel--;
					return ILogger::DEBUG;
				}
				$this->nestingLevel--;
				return $option['loglevel'] ?? ILogger::DEBUG;
			}
		}

		$this->nestingLevel--;
		return ILogger::WARN;
	}

	protected function checkLogSecret(string $conditionSecret): bool {
		$request = Server::get(IRequest::class);

		if ($request->getMethod() === 'PUT'
			&& !str_contains($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded')
			&& !str_contains($request->getHeader('Content-Type'), 'application/json')) {
			return hash_equals($conditionSecret, '');
		}

		// if token is found in the request change set the log condition to satisfied
		return hash_equals($conditionSecret, $request->getParam('log_secret', ''));
	}

	/**
	 * Logs an exception very detailed
	 *
	 * @param Exception|Throwable $exception
	 * @param array $context
	 * @return void
	 * @since 8.2.0
	 */
	public function logException(Throwable $exception, array $context = []): void {
		$app = $context['app'] ?? 'no app in context';
		$level = $context['level'] ?? ILogger::ERROR;

		$minLevel = $this->getLogLevel($context, $context['message'] ?? $exception->getMessage());
		if ($level < $minLevel
			&& (($this->crashReporters?->hasReporters() ?? false) === false)
			&& (($this->eventDispatcher?->hasListeners(BeforeMessageLoggedEvent::class) ?? false) === false)) {
			return; // no crash reporter, no listeners, we can stop for lower log level
		}

		// if an error is raised before the autoloader is properly setup, we can't serialize exceptions
		try {
			$serializer = $this->getSerializer();
		} catch (Throwable $e) {
			$this->error('Failed to load ExceptionSerializer serializer while trying to log ' . $exception->getMessage());
			return;
		}
		$data = $context;
		unset($data['app']);
		unset($data['level']);
		$data = array_merge($serializer->serializeException($exception), $data);
		$data = $this->interpolateMessage($data, isset($context['message']) && $context['message'] !== '' ? $context['message'] : ('Exception thrown: ' . get_class($exception)), 'CustomMessage');

		array_walk($context, [$this->normalizer, 'format']);

		$this->eventDispatcher?->dispatchTyped(new BeforeMessageLoggedEvent($app, $level, $data));

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

		$minLevel = $this->getLogLevel($context, $message);

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
	protected function writeLog(string $app, $entry, int $level): void {
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
			$coordinator = Server::get(Coordinator::class);
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
