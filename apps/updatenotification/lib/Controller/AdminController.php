<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
