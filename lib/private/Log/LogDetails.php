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

	public function logDetails(string $app, string|array $message, int $level): array {
		// default to ISO8601
		$format = $this->config->getValue('logdateformat', \DateTimeInterface::ATOM);
		$logTimeZone = $this->config->getValue('logtimezone', 'UTC');
		try {
			$timezone = new \DateTimeZone($logTimeZone);
		} catch (\Exception $e) {
			$timezone = new \DateTimeZone('UTC');
		}
		$time = \DateTime::createFromFormat('U.u', number_format(microtime(true), 4, '.', ''));
		if ($time === false) {
			$time = new \DateTime('now', $timezone);
		} else {
			// apply timezone if $time is created from UNIX timestamp
			$time->setTimezone($timezone);
		}
		$request = Server::get(IRequest::class);
		$reqId = $request->getId();
		$remoteAddr = $request->getRemoteAddress();
		// remove username/passwords from URLs before writing the to the log file
		$time = $time->format($format);
		$url = ($request->getRequestUri() !== '') ? $request->getRequestUri() : '--';
		$method = $request->getMethod();
		if ($this->config->getValue('installed', false)) {
			$user = \OC_User::getUser() ?: '--';
		} else {
			$user = '--';
		}
		$userAgent = $request->getHeader('User-Agent');
		if ($userAgent === '') {
			$userAgent = '--';
		}
		$version = $this->config->getValue('version', '');
		$scriptName = $request->getScriptName();
		$entry = compact(
			'reqId',
			'level',
			'time',
			'remoteAddr',
			'user',
			'app',
			'method',
			'url',
			'scriptName',
			'message',
			'userAgent',
			'version',
		);
		$clientReqId = $request->getHeader('X-Request-Id');
		if ($clientReqId !== '') {
			$entry['clientReqId'] = $clientReqId;
		}
		if (\OC::$CLI) {
			/* Only logging the command, not the parameters */
			$entry['occ_command'] = array_slice($_SERVER['argv'] ?? [], 0, 2);
		}

		if (is_array($message)) {
			// Exception messages are extracted and the exception is put into a separate field
			// anything else modern is split to 'message' (string) and
			// data (array) fields
			if (array_key_exists('Exception', $message)) {
				$entry['exception'] = $message;
				$entry['message'] = $message['CustomMessage'] !== '--' ? $message['CustomMessage'] : $message['Message'];
			} else {
				$entry['message'] = $message['message'] ?? '(no message provided)';
				unset($message['message']);
				$entry['data'] = $message;
			}
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
