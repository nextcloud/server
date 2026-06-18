<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Log;

use OC\SystemConfig;
use OCP\IRequest;
use OCP\Server;

abstract class LogDetails {
	public function __construct(
		private SystemConfig $config,
	) {
	}

	/**
	 * Build a structured log entry from request context and message data.
	 *
	 * Array messages are normalized into either an exception payload or a
	 * top-level message plus structured context data.
	 *
	 * @return array<string, mixed>
	 */
	public function logDetails(string $app, string|array $message, int $level): array {
		$version = $this->config->getValue('version', '');
		// Default to ATOM/ISO8601 formatting and UTC timezone.
		$format = $this->config->getValue('logdateformat', \DateTimeInterface::ATOM);
		$configuredTimeZone = $this->config->getValue('logtimezone', 'UTC');

		try {
			$timezone = new \DateTimeZone($configuredTimeZone);
		} catch (\Exception $e) {
			$timezone = new \DateTimeZone('UTC');
		}

		$timestamp = number_format(microtime(true), 4, '.', '');
		$time = \DateTime::createFromFormat('U.u', $timestamp);
		if ($time !== false) {
			// UNIX timestamps are timezone-independent; apply the configured log timezone.
			$time->setTimezone($timezone);
		} else {
			// Fall back to the current time in the configured timezone if parsing fails.
			$time = new \DateTime('now', $timezone);
		}
		$formattedTime = $time->format($format);

		$request = Server::get(IRequest::class);

		$reqId = $request->getId();
		$remoteAddr = $request->getRemoteAddress();
		$method = $request->getMethod();
		$url = $request->getRequestUri();
		$scriptName = $request->getScriptName();
		$userAgent = $request->getHeader('User-Agent');
		$clientReqId = $request->getHeader('X-Request-Id');

		if ($url === '') {
			$url = '--';
		}

		if ($userAgent === '') {
			$userAgent = '--';
		}

		$user = '--';
		if ($this->config->getValue('installed', false)) {
			$user = \OC_User::getUser() ?: '--';
		}

		$normalizedMessage = $message;
		$exception = null;
		$data = null;

		if (is_array($message)) {
			// Normalize array messages into one of two forms:
			// - exception payloads ('Exception' present): keep the full payload in 'exception'
			//   and derive the top-level 'message' from CustomMessage/Message
			// - structured payloads: use 'message' as the top-level message and store the
			//   remaining fields under 'data'
			if (array_key_exists('Exception', $message)) {
				$exception = $message;
				$normalizedMessage = $message['CustomMessage'] !== '--' ? $message['CustomMessage'] : $message['Message'];
			} else {
				$normalizedMessage = $message['message'] ?? '(no message provided)';
				unset($message['message']);
				$data = $message;
			}
		}

		$entry = [
			'reqId' => $reqId,
			'level' => $level,
			'time' => $formattedTime,
			'remoteAddr' => $remoteAddr,
			'user' => $user,
			'app' => $app,
			'method' => $method,
			'url' => $url,
			'scriptName' => $scriptName,
			'message' => $normalizedMessage,
			'userAgent' => $userAgent,
			'version' => $version,
		];

		if ($clientReqId !== '') {
			$entry['clientReqId'] = $clientReqId;
		}

		if (\OC::$CLI) {
			// Only logging the command, not the parameters
			$entry['occ_command'] = array_slice($_SERVER['argv'] ?? [], 0, 2);
		}

		if ($exception !== null) {
			$entry['exception'] = $exception;
		}
		if ($data !== null) {
			$entry['data'] = $data;
		}

		return $entry;
	}

	public function logDetailsAsJSON(string $app, string|array $message, int $level): string {
		$entry = $this->logDetails($app, $message, $level);
		// PHP's json_encode only accept proper UTF-8 strings, loop over all
		// elements to ensure that they are properly UTF-8 compliant or convert
		// them manually.
		foreach ($entry as $key => $value) {
			if (is_string($value)) {
				$testEncode = json_encode($value, JSON_UNESCAPED_SLASHES);
				if ($testEncode === false) {
					$entry[$key] = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value));
				}
			}
		}
		return json_encode($entry, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);
	}
}
