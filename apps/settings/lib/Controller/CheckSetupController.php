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

use DirectoryIterator;
use GuzzleHttp\Exception\ClientException;
use OC;
use OC\AppFramework\Http;
use OC\IntegrityCheck\Checker;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\IgnoreOpenAPI;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IServerContainer;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Lock\ILockingProvider;
use OCP\Notification\IManager;
use OCP\SetupCheck\ISetupCheckManager;
use Psr\Log\LoggerInterface;

#[IgnoreOpenAPI]
class CheckSetupController extends Controller {
	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var Checker */
	private $checker;
	/** @var LoggerInterface */
	private $logger;
	/** @var IEventDispatcher */
	private $dispatcher;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IDateTimeFormatter */
	private $dateTimeFormatter;
	/** @var ITempManager */
	private $tempManager;
	/** @var IManager */
	private $manager;
	/** @var IAppManager */
	private $appManager;
	/** @var IServerContainer */
	private $serverContainer;
	private ISetupCheckManager $setupCheckManager;

	public function __construct($AppName,
		IRequest $request,
		IConfig $config,
		IClientService $clientService,
		IURLGenerator $urlGenerator,
		IL10N $l10n,
		Checker $checker,
		LoggerInterface $logger,
		IEventDispatcher $dispatcher,
		ILockingProvider $lockingProvider,
		IDateTimeFormatter $dateTimeFormatter,
		ITempManager $tempManager,
		IManager $manager,
		IAppManager $appManager,
		IServerContainer $serverContainer,
		ISetupCheckManager $setupCheckManager,
	) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->clientService = $clientService;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->checker = $checker;
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
		$this->lockingProvider = $lockingProvider;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->tempManager = $tempManager;
		$this->manager = $manager;
		$this->appManager = $appManager;
		$this->serverContainer = $serverContainer;
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
	 * Public for the sake of unit-testing
	 *
	 * @return array
	 */
	protected function getCurlVersion() {
		return curl_version();
	}

	/**
	 * Check if the used SSL lib is outdated. Older OpenSSL and NSS versions do
	 * have multiple bugs which likely lead to problems in combination with
	 * functionality required by ownCloud such as SNI.
	 *
	 * @link https://github.com/owncloud/core/issues/17446#issuecomment-122877546
	 * @link https://bugzilla.redhat.com/show_bug.cgi?id=1241172
	 * @return string
	 */
	private function isUsedTlsLibOutdated() {
		// Don't run check when:
		// 1. Server has `has_internet_connection` set to false
		// 2. AppStore AND S2S is disabled
		if (!$this->config->getSystemValue('has_internet_connection', true)) {
			return '';
		}
		if (!$this->config->getSystemValue('appstoreenabled', true)
			&& $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'no'
			&& $this->config->getAppValue('files_sharing', 'incoming_server2server_share_enabled', 'yes') === 'no') {
			return '';
		}

		$versionString = $this->getCurlVersion();
		if (isset($versionString['ssl_version'])) {
			$versionString = $versionString['ssl_version'];
		} else {
			return '';
		}

		$features = $this->l10n->t('installing and updating apps via the App Store or Federated Cloud Sharing');
		if (!$this->config->getSystemValue('appstoreenabled', true)) {
			$features = $this->l10n->t('Federated Cloud Sharing');
		}

		// Check if NSS and perform heuristic check
		if (str_starts_with($versionString, 'NSS/')) {
			try {
				$firstClient = $this->clientService->newClient();
				$firstClient->get('https://nextcloud.com/');

				$secondClient = $this->clientService->newClient();
				$secondClient->get('https://nextcloud.com/');
			} catch (ClientException $e) {
				if ($e->getResponse()->getStatusCode() === 400) {
					return $this->l10n->t('cURL is using an outdated %1$s version (%2$s). Please update your operating system or features such as %3$s will not work reliably.', ['NSS', $versionString, $features]);
				}
			} catch (\Exception $e) {
				$this->logger->warning('error checking curl', [
					'app' => 'settings',
					'exception' => $e,
				]);
				return $this->l10n->t('Could not determine if TLS version of cURL is outdated or not because an error happened during the HTTPS request against https://nextcloud.com. Please check the Nextcloud log file for more details.');
			}
		}

		return '';
	}

	/**
	 * Checks if the correct memcache module for PHP is installed. Only
	 * fails if memcached is configured and the working module is not installed.
	 *
	 * @return bool
	 */
	private function isCorrectMemcachedPHPModuleInstalled() {
		$memcacheDistributedClass = $this->config->getSystemValue('memcache.distributed', null);
		if ($memcacheDistributedClass === null || ltrim($memcacheDistributedClass, '\\') !== \OC\Memcache\Memcached::class) {
			return true;
		}

		// there are two different memcache modules for PHP
		// we only support memcached and not memcache
		// https://code.google.com/p/memcached/wiki/PHPClientComparison
		return !(!extension_loaded('memcached') && extension_loaded('memcache'));
	}

	/**
	 * Checks if set_time_limit is not disabled.
	 *
	 * @return bool
	 */
	private function isSettimelimitAvailable() {
		if (function_exists('set_time_limit')
			&& !str_contains(ini_get('disable_functions'), 'set_time_limit')) {
			return true;
		}

		return false;
	}

	/**
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

	protected function getSuggestedOverwriteCliURL(): string {
		$currentOverwriteCliUrl = $this->config->getSystemValue('overwrite.cli.url', '');
		$suggestedOverwriteCliUrl = $this->request->getServerProtocol() . '://' . $this->request->getInsecureServerHost() . \OC::$WEBROOT;

		// Check correctness by checking if it is a valid URL
		if (filter_var($currentOverwriteCliUrl, FILTER_VALIDATE_URL)) {
			$suggestedOverwriteCliUrl = '';
		}

		return $suggestedOverwriteCliUrl;
	}

	protected function getLastCronInfo(): array {
		$lastCronRun = (int)$this->config->getAppValue('core', 'lastcron', '0');
		return [
			'diffInSeconds' => time() - $lastCronRun,
			'relativeTime' => $this->dateTimeFormatter->formatTimeSpan($lastCronRun),
			'backgroundJobsUrl' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'server']) . '#backgroundjobs',
		];
	}

	protected function getCronErrors() {
		$errors = json_decode($this->config->getAppValue('core', 'cronErrors', ''), true);

		if (is_array($errors)) {
			return $errors;
		}

		return [];
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

	/**
	 * Iterates through the configured app roots and
	 * tests if the subdirectories are owned by the same user than the current user.
	 *
	 * @return array
	 */
	protected function getAppDirsWithDifferentOwner(): array {
		$currentUser = posix_getuid();
		$appDirsWithDifferentOwner = [[]];

		foreach (OC::$APPSROOTS as $appRoot) {
			if ($appRoot['writable'] === true) {
				$appDirsWithDifferentOwner[] = $this->getAppDirsWithDifferentOwnerForAppRoot($currentUser, $appRoot);
			}
		}

		$appDirsWithDifferentOwner = array_merge(...$appDirsWithDifferentOwner);
		sort($appDirsWithDifferentOwner);

		return $appDirsWithDifferentOwner;
	}

	/**
	 * Tests if the directories for one apps directory are writable by the current user.
	 *
	 * @param int $currentUser The current user
	 * @param array $appRoot The app root config
	 * @return string[] The none writable directory paths inside the app root
	 */
	private function getAppDirsWithDifferentOwnerForAppRoot(int $currentUser, array $appRoot): array {
		$appDirsWithDifferentOwner = [];
		$appsPath = $appRoot['path'];
		$appsDir = new DirectoryIterator($appRoot['path']);

		foreach ($appsDir as $fileInfo) {
			if ($fileInfo->isDir() && !$fileInfo->isDot()) {
				$absAppPath = $appsPath . DIRECTORY_SEPARATOR . $fileInfo->getFilename();
				$appDirUser = fileowner($absAppPath);
				if ($appDirUser !== $currentUser) {
					$appDirsWithDifferentOwner[] = $absAppPath;
				}
			}
		}

		return $appDirsWithDifferentOwner;
	}

	protected function isImagickEnabled(): bool {
		if ($this->config->getAppValue('theming', 'enabled', 'no') === 'yes') {
			if (!extension_loaded('imagick')) {
				return false;
			}
		}
		return true;
	}

	protected function areWebauthnExtensionsEnabled(): bool {
		if (!extension_loaded('bcmath')) {
			return false;
		}
		if (!extension_loaded('gmp')) {
			return false;
		}
		return true;
	}

	protected function isMysqlUsedWithoutUTF8MB4(): bool {
		return ($this->config->getSystemValue('dbtype', 'sqlite') === 'mysql') && ($this->config->getSystemValue('mysql.utf8mb4', false) === false);
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

	protected function imageMagickLacksSVGSupport(): bool {
		return extension_loaded('imagick') && count(\Imagick::queryFormats('SVG')) === 0;
	}

	/**
	 * @return DataResponse
	 * @AuthorizedAdminSetting(settings=OCA\Settings\Settings\Admin\Overview)
	 */
	public function check() {
		return new DataResponse(
			[
				'suggestedOverwriteCliURL' => $this->getSuggestedOverwriteCliURL(),
				'cronInfo' => $this->getLastCronInfo(),
				'cronErrors' => $this->getCronErrors(),
				'isFairUseOfFreePushService' => $this->isFairUseOfFreePushService(),
				'isUsedTlsLibOutdated' => $this->isUsedTlsLibOutdated(),
				'reverseProxyDocs' => $this->urlGenerator->linkToDocs('admin-reverse-proxy'),
				'isCorrectMemcachedPHPModuleInstalled' => $this->isCorrectMemcachedPHPModuleInstalled(),
				'hasPassedCodeIntegrityCheck' => $this->checker->hasPassedCheck(),
				'codeIntegrityCheckerDocumentation' => $this->urlGenerator->linkToDocs('admin-code-integrity'),
				'isSettimelimitAvailable' => $this->isSettimelimitAvailable(),
				'appDirsWithDifferentOwner' => $this->getAppDirsWithDifferentOwner(),
				'isImagickEnabled' => $this->isImagickEnabled(),
				'areWebauthnExtensionsEnabled' => $this->areWebauthnExtensionsEnabled(),
				'isMysqlUsedWithoutUTF8MB4' => $this->isMysqlUsedWithoutUTF8MB4(),
				'isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed' => $this->isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed(),
				'reverseProxyGeneratedURL' => $this->urlGenerator->getAbsoluteURL('index.php'),
				'imageMagickLacksSVGSupport' => $this->imageMagickLacksSVGSupport(),
				'temporaryDirectoryWritable' => $this->isTemporaryDirectoryWritable(),
				'generic' => $this->setupCheckManager->runAll(),
			]
		);
	}
}
