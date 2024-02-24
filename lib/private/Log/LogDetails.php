<?php
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Log;

use OC\SystemConfig;

abstract class LogDetails {
	public function __construct(
		private SystemConfig $config,
	) {
	}

	public function logDetails(string $app, $message, int $level): array {
		// default to ISO8601
		$format = $this->config->getValue('logdateformat', \DateTimeInterface::ATOM);
		$logTimeZone = $this->config->getValue('logtimezone', 'UTC');
		try {
			$timezone = new \DateTimeZone($logTimeZone);
		} catch (\Exception $e) {
			$timezone = new \DateTimeZone('UTC');
		}
		$time = \DateTime::createFromFormat("U.u", number_format(microtime(true), 4, ".", ""));
		if ($time === false) {
			$time = new \DateTime('now', $timezone);
		} else {
			// apply timezone if $time is created from UNIX timestamp
			$time->setTimezone($timezone);
		}
		$request = \OC::$server->getRequest();
		$reqId = $request->getId();
		$remoteAddr = $request->getRemoteAddress();
		// remove username/passwords from URLs before writing the to the log file
		$time = $time->format($format);
		$url = ($request->getRequestUri() !== '') ? $request->getRequestUri() : '--';
		$method = is_string($request->getMethod()) ? $request->getMethod() : '--';
		if ($this->config->getValue('installed', false)) {
			$user = \OC_User::getUser() ? \OC_User::getUser() : '--';
		} else {
			$user = '--';
		}
		$userAgent = $request->getHeader('User-Agent');
		if ($userAgent === '') {
			$userAgent = '--';
		}
		$version = $this->config->getValue('version', '');
		$entry = compact(
			'reqId',
			'level',
			'time',
			'remoteAddr',
			'user',
			'app',
			'method',
			'url',
			'message',
			'userAgent',
			'version'
		);

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

	public function logDetailsAsJSON(string $app, $message, int $level): string {
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
