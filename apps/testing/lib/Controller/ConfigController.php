<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Testing\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;

class ConfigController extends OCSController {

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private IConfig $config,
		private IAppConfig $appConfig,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param string $appid
	 * @param string $configkey
	 * @param string $value
	 * @return DataResponse
	 */
	public function setAppValue($appid, $configkey, $value) {
		$this->appConfig->setValue($appid, $configkey, $value);
		return new DataResponse();
	}

	/**
	 * @param string $appid
	 * @param string $configkey
	 * @return DataResponse
	 */
	public function deleteAppValue($appid, $configkey) {
		$this->appConfig->deleteKey($appid, $configkey);
		return new DataResponse();
	}
}
