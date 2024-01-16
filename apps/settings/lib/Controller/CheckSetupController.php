<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Cthulhux <git@tuxproject.de>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Derek <derek.kelly27@gmail.com>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ko- <k.stoffelen@cs.ru.nl>
 * @author Lauris Binde <laurisb@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author nhirokinet <nhirokinet@nhiroki.net>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sven Strickroth <email@cs-ware.de>
 * @author Sylvia van Os <sylvia@hackerchick.me>
 * @author timm2k <timm2k@gmx.de>
 * @author Timo Förster <tfoerster@webfoersterei.de>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
 * @author MichaIng <micha@dietpi.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCA\Settings\Controller;

use OC\AppFramework\Http;
use OC\IntegrityCheck\Checker;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Notification\IManager;
use OCP\SetupCheck\ISetupCheckManager;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class CheckSetupController extends Controller {
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var Checker */
	private $checker;
	/** @var LoggerInterface */
	private $logger;
	/** @var ITempManager */
	private $tempManager;
	/** @var IManager */
	private $manager;
	private ISetupCheckManager $setupCheckManager;

	public function __construct($AppName,
		IRequest $request,
		IConfig $config,
		IURLGenerator $urlGenerator,
		IL10N $l10n,
		Checker $checker,
		LoggerInterface $logger,
		ITempManager $tempManager,
		IManager $manager,
		ISetupCheckManager $setupCheckManager,
	) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->checker = $checker;
		$this->logger = $logger;
		$this->tempManager = $tempManager;
		$this->manager = $manager;
		$this->setupCheckManager = $setupCheckManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function setupCheckManager(): DataResponse {
		return new DataResponse($this->setupCheckManager->runAll());
	}

	/**
	 * Check if is fair use of free push service
	 * @return bool
	 */
	private function isFairUseOfFreePushService(): bool {
		$rateLimitReached = (int) $this->config->getAppValue('notifications', 'rate_limit_reached', '0');
		if ($rateLimitReached >= (time() - 7 * 24 * 3600)) {
			// Notifications app is showing a message already
			return true;
		}
		return $this->manager->isFairUseOfFreePushService();
	}

	/**
	 * @NoCSRFRequired
	 * @return RedirectResponse
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\Overview)
	 */
	public function rescanFailedIntegrityCheck(): RedirectResponse {
		$this->checker->runInstanceVerification();
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'overview'])
		);
	}

	/**
	 * @NoCSRFRequired
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\Overview)
	 */
	public function getFailedIntegrityCheckFiles(): DataDisplayResponse {
		if (!$this->checker->isCodeCheckEnforced()) {
			return new DataDisplayResponse('Integrity checker has been disabled. Integrity cannot be verified.');
		}

		$completeResults = $this->checker->getResults();

		if (!empty($completeResults)) {
			$formattedTextResponse = 'Technical information
=====================
The following list covers which files have failed the integrity check. Please read
the previous linked documentation to learn more about the errors and how to fix
them.

Results
=======
';
			foreach ($completeResults as $context => $contextResult) {
				$formattedTextResponse .= "- $context\n";

				foreach ($contextResult as $category => $result) {
					$formattedTextResponse .= "\t- $category\n";
					if ($category !== 'EXCEPTION') {
						foreach ($result as $key => $results) {
							$formattedTextResponse .= "\t\t- $key\n";
						}
					} else {
						foreach ($result as $key => $results) {
							$formattedTextResponse .= "\t\t- $results\n";
						}
					}
				}
			}

			$formattedTextResponse .= '
Raw output
==========
';
			$formattedTextResponse .= print_r($completeResults, true);
		} else {
			$formattedTextResponse = 'No errors have been found.';
		}


		return new DataDisplayResponse(
			$formattedTextResponse,
			Http::STATUS_OK,
			[
				'Content-Type' => 'text/plain',
			]
		);
	}

	private function isTemporaryDirectoryWritable(): bool {
		try {
			if (!empty($this->tempManager->getTempBaseDir())) {
				return true;
			}
		} catch (\Exception $e) {
		}
		return false;
	}

	protected function isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed(): bool {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultibucket = $this->config->getSystemValue('objectstore_multibucket', null);

		if (!isset($objectStoreMultibucket) && !isset($objectStore)) {
			return true;
		}

		if (isset($objectStoreMultibucket['class']) && $objectStoreMultibucket['class'] !== 'OC\\Files\\ObjectStore\\S3') {
			return true;
		}

		if (isset($objectStore['class']) && $objectStore['class'] !== 'OC\\Files\\ObjectStore\\S3') {
			return true;
		}

		$tempPath = sys_get_temp_dir();
		if (!is_dir($tempPath)) {
			$this->logger->error('Error while checking the temporary PHP path - it was not properly set to a directory. Returned value: ' . $tempPath);
			return false;
		}
		$freeSpaceInTemp = function_exists('disk_free_space') ? disk_free_space($tempPath) : false;
		if ($freeSpaceInTemp === false) {
			$this->logger->error('Error while checking the available disk space of temporary PHP path or no free disk space returned. Temporary path: ' . $tempPath);
			return false;
		}

		$freeSpaceInTempInGB = $freeSpaceInTemp / 1024 / 1024 / 1024;
		if ($freeSpaceInTempInGB > 50) {
			return true;
		}

		$this->logger->warning('Checking the available space in the temporary path resulted in ' . round($freeSpaceInTempInGB, 1) . ' GB instead of the recommended 50GB. Path: ' . $tempPath);
		return false;
	}

	/**
	 * @return DataResponse
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\Overview)
	 */
	public function check() {
		return new DataResponse(
			[
				'isFairUseOfFreePushService' => $this->isFairUseOfFreePushService(),
				'reverseProxyDocs' => $this->urlGenerator->linkToDocs('admin-reverse-proxy'),
				'isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed' => $this->isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed(),
				'reverseProxyGeneratedURL' => $this->urlGenerator->getAbsoluteURL('index.php'),
				'temporaryDirectoryWritable' => $this->isTemporaryDirectoryWritable(),
				'generic' => $this->setupCheckManager->runAll(),
			]
		);
	}
}
