<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\IEventSource;
use OCP\IRequest;

class EventSource implements IEventSource {
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
		header('Content-Type: text/event-stream');
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
	 * @throws \BadMethodCallException
	 * @suppress PhanDeprecatedFunction
	 */
	#[\Override]
	public function send(string $type, mixed $data = null): void {
		if ($data && !preg_match('/^[A-Za-z0-9_]+$/', $type)) {
			throw new \BadMethodCallException('Type needs to be alphanumeric (' . $type . ')');
		}
		$this->init();
		if (is_null($data)) {
			$data = $type;
			$type = null;
		}
		if ($type) {
			echo 'event: ' . $type . PHP_EOL;
		}
		echo 'data: ' . json_encode($data, JSON_HEX_TAG) . PHP_EOL;
		echo PHP_EOL;
		flush();
	}

	#[\Override]
	public function close(): void {
		$this->send('__internal__', 'close'); //server side closing can be an issue, let the client do it
	}
}
