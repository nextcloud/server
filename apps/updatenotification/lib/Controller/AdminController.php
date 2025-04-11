<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Controller;

use OCA\UpdateNotification\BackgroundJob\ResetToken;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Util;

class AdminController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IJobList $jobList,
		private ISecureRandom $secureRandom,
		private IConfig $config,
		private IAppConfig $appConfig,
		private ITimeFactory $timeFactory,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	private function isUpdaterEnabled() {
		return !$this->config->getSystemValue('upgrade.disable-web', false);
	}

	/**
	 * @param string $channel
	 * @return DataResponse
	 */
	public function setChannel(string $channel): DataResponse {
		Util::setChannel($channel);
		$this->appConfig->setValueInt('core', 'lastupdatedat', 0);
		return new DataResponse(['status' => 'success', 'data' => ['message' => $this->l10n->t('Channel updated')]]);
	}

	/**
	 * @return DataResponse
	 */
	public function createCredentials(): DataResponse {
		if (!$this->isUpdaterEnabled()) {
			return new DataResponse(['status' => 'error', 'message' => $this->l10n->t('Web updater is disabled')], Http::STATUS_FORBIDDEN);
		}

		// Create a new job and store the creation date
		$this->jobList->add(ResetToken::class);
		$this->appConfig->setValueInt('core', 'updater.secret.created', $this->timeFactory->getTime());

		// Create a new token
		$newToken = $this->secureRandom->generate(64);
		$this->config->setSystemValue('updater.secret', password_hash($newToken, PASSWORD_DEFAULT));

		return new DataResponse($newToken);
	}
}
