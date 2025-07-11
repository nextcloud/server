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
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
	 * @return StreamResponse<Http::STATUS_OK, array{Content-Type: 'application/octet-stream', 'Content-Disposition': 'attachment; filename="nextcloud.log"'}>
	 *
	 * 200: Logfile returned
	 */
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	public function download() {
		if (!$this->log instanceof Log) {
			throw new \UnexpectedValueException('Log file not available');
		}
		return new StreamResponse(
			$this->log->getLogPath(),
			Http::STATUS_OK,
			[
				'Content-Type' => 'application/octet-stream',
				'Content-Disposition' => 'attachment; filename="nextcloud.log"',
			],
		);
	}
}
