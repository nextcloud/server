<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\IEventSource;
use OCP\IRequest;

class EventSource implements IEventSource {
	private bool $fallback = false;
	private int $fallBackId = 0;
	private bool $started = false;

	public function __construct(
		private IRequest $request,
	) {
	}

	protected function init(): void {
		if ($this->started) {
			return;
		}
		$this->started = true;

		// prevent php output buffering, caching and nginx buffering
		\OC_Util::obEnd();
		header('Cache-Control: no-cache');
		header('X-Accel-Buffering: no');
		$this->fallback = isset($_GET['fallback']) and $_GET['fallback'] == 'true';
		if ($this->fallback) {
			$this->fallBackId = (int)$_GET['fallback_id'];
			/**
			 * FIXME: The default content-security-policy of ownCloud forbids inline
			 * JavaScript for security reasons. IE starting on Windows 10 will
			 * however also obey the CSP which will break the event source fallback.
			 *
			 * As a workaround thus we set a custom policy which allows the execution
			 * of inline JavaScript.
			 *
			 * @link https://github.com/owncloud/core/issues/14286
			 */
			header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'");
			header('Content-Type: text/html');
			echo str_repeat('<span></span>' . PHP_EOL, 10); //dummy data to keep IE happy
		} else {
			header('Content-Type: text/event-stream');
		}
		if (!$this->request->passesStrictCookieCheck()) {
			header('Location: ' . \OC::$WEBROOT);
			exit();
		}
		if (!$this->request->passesCSRFCheck()) {
			$this->send('error', 'Possible CSRF attack. Connection will be closed.');
			$this->close();
			exit();
		}
		flush();
	}

	/**
	 * send a message to the client
	 *
	 * @param string $type
	 * @param mixed $data
	 *
	 * @throws \BadMethodCallException
	 *                                 if only one parameter is given, a typeless message will be send with that parameter as data
	 * @suppress PhanDeprecatedFunction
	 */
	public function send($type, $data = null) {
		if ($data and !preg_match('/^[A-Za-z0-9_]+$/', $type)) {
			throw new \BadMethodCallException('Type needs to be alphanumeric (' . $type . ')');
		}
		$this->init();
		if (is_null($data)) {
			$data = $type;
			$type = null;
		}
		if ($this->fallback) {
			$response = '<script type="text/javascript">window.parent.OC.EventSource.fallBackCallBack('
				. $this->fallBackId . ',"' . ($type ?? '') . '",' . json_encode($data, JSON_HEX_TAG) . ')</script>' . PHP_EOL;
			echo $response;
		} else {
			if ($type) {
				echo 'event: ' . $type . PHP_EOL;
			}
			echo 'data: ' . json_encode($data, JSON_HEX_TAG) . PHP_EOL;
		}
		echo PHP_EOL;
		flush();
	}

	/**
	 * close the connection of the event source
	 */
	public function close() {
		$this->send('__internal__', 'close'); //server side closing can be an issue, let the client do it
	}
}
