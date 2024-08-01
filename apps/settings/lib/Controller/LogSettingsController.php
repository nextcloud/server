<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Controller;

use OC\Log;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;

class LogSettingsController extends Controller {

	/** @var Log */
	private $log;

	public function __construct(string $appName, IRequest $request, Log $logger) {
		parent::__construct($appName, $request);
		$this->log = $logger;
	}

	/**
	 * download logfile
	 *
	 * @NoCSRFRequired
	 *
	 * @psalm-suppress MoreSpecificReturnType The value of Content-Disposition is not relevant
	 * @psalm-suppress LessSpecificReturnStatement The value of Content-Disposition is not relevant
	 * @return StreamResponse<Http::STATUS_OK, array{Content-Type: 'application/octet-stream', 'Content-Disposition': string}>
	 *
	 * 200: Logfile returned
	 */
	public function download() {
		if (!$this->log instanceof Log) {
			throw new \UnexpectedValueException('Log file not available');
		}
		$resp = new StreamResponse($this->log->getLogPath());
		$resp->setHeaders([
			'Content-Type' => 'application/octet-stream',
			'Content-Disposition' => 'attachment; filename="nextcloud.log"',
		]);
		return $resp;
	}
}
