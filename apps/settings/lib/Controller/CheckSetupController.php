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

use bantu\IniGetWrapper\IniGetWrapper;
use DirectoryIterator;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\TransactionIsolationLevel;
use GuzzleHttp\Exception\ClientException;
use OC;
use OC\AppFramework\Http;
use OC\DB\Connection;
use OC\DB\MissingColumnInformation;
use OC\DB\MissingIndexInformation;
use OC\DB\MissingPrimaryKeyInformation;
use OC\DB\SchemaWrapper;
use OC\IntegrityCheck\Checker;
use OC\Lock\NoopLockingProvider;
use OC\MemoryInfo;
use OCA\Settings\SetupChecks\CheckUserCertificates;
use OCA\Settings\SetupChecks\LdapInvalidUuids;
use OCA\Settings\SetupChecks\LegacySSEKeyFormat;
use OCA\Settings\SetupChecks\PhpDefaultCharset;
use OCA\Settings\SetupChecks\PhpOutputBuffering;
use OCA\Settings\SetupChecks\SupportedDatabase;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\DB\Types;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IServerContainer;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Lock\ILockingProvider;
use OCP\Notification\IManager;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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
	/** @var EventDispatcherInterface */
	private $dispatcher;
	/** @var Connection */
	private $db;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IDateTimeFormatter */
	private $dateTimeFormatter;
	/** @var MemoryInfo */
	private $memoryInfo;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var IniGetWrapper */
	private $iniGetWrapper;
	/** @var IDBConnection */
	private $connection;
	/** @var ITempManager */
	private $tempManager;
	/** @var IManager */
	private $manager;
	/** @var IAppManager */
	private $appManager;
	/** @var IServerContainer */
	private $serverContainer;

	public function __construct($AppName,
								IRequest $request,
								IConfig $config,
								IClientService $clientService,
								IURLGenerator $urlGenerator,
								IL10N $l10n,
								Checker $checker,
								LoggerInterface $logger,
								EventDispatcherInterface $dispatcher,
								Connection $db,
								ILockingProvider $lockingProvider,
								IDateTimeFormatter $dateTimeFormatter,
								MemoryInfo $memoryInfo,
								ISecureRandom $secureRandom,
								IniGetWrapper $iniGetWrapper,
								IDBConnection $connection,
								ITempManager $tempManager,
								IManager $manager,
								IAppManager $appManager,
								IServerContainer $serverContainer
	) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->clientService = $clientService;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->checker = $checker;
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
		$this->db = $db;
		$this->lockingProvider = $lockingProvider;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->memoryInfo = $memoryInfo;
		$this->secureRandom = $secureRandom;
		$this->iniGetWrapper = $iniGetWrapper;
		$this->connection = $connection;
		$this->tempManager = $tempManager;
		$this->manager = $manager;
		$this->appManager = $appManager;
		$this->serverContainer = $serverContainer;
	}

	/**
	 * Check if is fair use of free push service
	 * @return bool
	 */
	private function isFairUseOfFreePushService(): bool {
		return $this->manager->isFairUseOfFreePushService();
	}

	/**
	 * Checks if the server can connect to the internet using HTTPS and HTTP
	 * @return bool
	 */
	private function hasInternetConnectivityProblems(): bool {
		if ($this->config->getSystemValue('has_internet_connection', true) === false) {
			return false;
		}

		$siteArray = $this->config->getSystemValue('connectivity_check_domains', [
			'www.nextcloud.com', 'www.startpage.com', 'www.eff.org', 'www.edri.org'
		]);

		foreach ($siteArray as $site) {
			if ($this->isSiteReachable($site)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if the Nextcloud server can connect to a specific URL
	 * @param string $site site domain or full URL with http/https protocol
	 * @return bool
	 */
	private function isSiteReachable(string $site): bool {
		try {
			$client = $this->clientService->newClient();
			// if there is no protocol, test http:// AND https://
			if (preg_match('/^https?:\/\//', $site) !== 1) {
				$httpSite = 'http://' . $site . '/';
				$client->get($httpSite);
				$httpsSite = 'https://' . $site . '/';
				$client->get($httpsSite);
			} else {
				$client->get($site);
			}
		} catch (\Exception $e) {
			$this->logger->error('Cannot connect to: ' . $site, [
				'app' => 'internet_connection_check',
				'exception' => $e,
			]);
			return false;
		}
		return true;
	}

	/**
	 * Checks whether a local memcache is installed or not
	 * @return bool
	 */
	private function isMemcacheConfigured() {
		return $this->config->getSystemValue('memcache.local', null) !== null;
	}

	/**
	 * Whether PHP can generate "secure" pseudorandom integers
	 *
	 * @return bool
	 */
	private function isRandomnessSecure() {
		try {
			$this->secureRandom->generate(1);
		} catch (\Exception $ex) {
			return false;
		}
		return true;
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

		// Check if at least OpenSSL after 1.01d or 1.0.2b
		if (strpos($versionString, 'OpenSSL/') === 0) {
			$majorVersion = substr($versionString, 8, 5);
			$patchRelease = substr($versionString, 13, 6);

			if (($majorVersion === '1.0.1' && ord($patchRelease) < ord('d')) ||
				($majorVersion === '1.0.2' && ord($patchRelease) < ord('b'))) {
				return $this->l10n->t('cURL is using an outdated %1$s version (%2$s). Please update your operating system or features such as %3$s will not work reliably.', ['OpenSSL', $versionString, $features]);
			}
		}

		// Check if NSS and perform heuristic check
		if (strpos($versionString, 'NSS/') === 0) {
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
	 * Whether the version is outdated
	 *
	 * @return bool
	 */
	protected function isPhpOutdated(): bool {
		return PHP_VERSION_ID < 80000;
	}

	/**
	 * Whether the php version is still supported (at time of release)
	 * according to: https://www.php.net/supported-versions.php
	 *
	 * @return array
	 */
	private function isPhpSupported(): array {
		return ['eol' => $this->isPhpOutdated(), 'version' => PHP_VERSION];
	}

	/**
	 * Check if the reverse proxy configuration is working as expected
	 *
	 * @return bool
	 */
	private function forwardedForHeadersWorking(): bool {
		$trustedProxies = $this->config->getSystemValue('trusted_proxies', []);
		$remoteAddress = $this->request->getHeader('REMOTE_ADDR');

		if (empty($trustedProxies) && $this->request->getHeader('X-Forwarded-Host') !== '') {
			return false;
		}

		if (\is_array($trustedProxies)) {
			if (\in_array($remoteAddress, $trustedProxies, true) && $remoteAddress !== '127.0.0.1') {
				return $remoteAddress !== $this->request->getRemoteAddress();
			}
		} else {
			return false;
		}

		// either not enabled or working correctly
		return true;
	}

	/**
	 * Checks if the correct memcache module for PHP is installed. Only
	 * fails if memcached is configured and the working module is not installed.
	 *
	 * @return bool
	 */
	private function isCorrectMemcachedPHPModuleInstalled() {
		if ($this->config->getSystemValue('memcache.distributed', null) !== '\OC\Memcache\Memcached') {
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
			&& strpos(ini_get('disable_functions'), 'set_time_limit') === false) {
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

	/**
	 * Checks whether a PHP OPcache is properly set up
	 * @return string[] The list of OPcache setup recommendations
	 */
	protected function getOpcacheSetupRecommendations(): array {
		// If the module is not loaded, return directly to skip inapplicable checks
		if (!extension_loaded('Zend OPcache')) {
			return [$this->l10n->t('The PHP OPcache module is not loaded. For better performance it is recommended to load it into your PHP installation.')];
		}

		$recommendations = [];

		// Check whether Nextcloud is allowed to use the OPcache API
		$isPermitted = true;
		$permittedPath = $this->iniGetWrapper->getString('opcache.restrict_api');
		if (isset($permittedPath) && $permittedPath !== '' && !str_starts_with(\OC::$SERVERROOT, rtrim($permittedPath, '/'))) {
			$isPermitted = false;
		}

		if (!$this->iniGetWrapper->getBool('opcache.enable')) {
			$recommendations[] = $this->l10n->t('OPcache is disabled. For better performance, it is recommended to apply <code>opcache.enable=1</code> to your PHP configuration.');

			// Check for saved comments only when OPcache is currently disabled. If it was enabled, opcache.save_comments=0 would break Nextcloud in the first place.
			if (!$this->iniGetWrapper->getBool('opcache.save_comments')) {
				$recommendations[] = $this->l10n->t('OPcache is configured to remove code comments. With OPcache enabled, <code>opcache.save_comments=1</code> must be set for Nextcloud to function.');
			}

			if (!$isPermitted) {
				$recommendations[] = $this->l10n->t('Nextcloud is not allowed to use the OPcache API. With OPcache enabled, it is highly recommended to include all Nextcloud directories with <code>opcache.restrict_api</code> or unset this setting to disable OPcache API restrictions, to prevent errors during Nextcloud core or app upgrades.');
			}
		} elseif (!$isPermitted) {
			$recommendations[] = $this->l10n->t('Nextcloud is not allowed to use the OPcache API. It is highly recommended to include all Nextcloud directories with <code>opcache.restrict_api</code> or unset this setting to disable OPcache API restrictions, to prevent errors during Nextcloud core or app upgrades.');
		} elseif ($this->iniGetWrapper->getBool('opcache.file_cache_only')) {
			$recommendations[] = $this->l10n->t('The shared memory based OPcache is disabled. For better performance, it is recommended to apply <code>opcache.file_cache_only=0</code> to your PHP configuration and use the file cache as second level cache only.');
		} else {
			// Check whether opcache_get_status has been explicitly disabled an in case skip usage based checks
			$disabledFunctions = $this->iniGetWrapper->getString('disable_functions');
			if (isset($disabledFunctions) && str_contains($disabledFunctions, 'opcache_get_status')) {
				return [];
			}

			$status = opcache_get_status(false);

			// Recommend to raise value, if more than 90% of max value is reached
			if (
				empty($status['opcache_statistics']['max_cached_keys']) ||
				($status['opcache_statistics']['num_cached_keys'] / $status['opcache_statistics']['max_cached_keys'] > 0.9)
			) {
				$recommendations[] = $this->l10n->t('The maximum number of OPcache keys is nearly exceeded. To assure that all scripts can be kept in the cache, it is recommended to apply <code>opcache.max_accelerated_files</code> to your PHP configuration with a value higher than <code>%s</code>.', [($this->iniGetWrapper->getNumeric('opcache.max_accelerated_files') ?: 'currently')]);
			}

			if (
				empty($status['memory_usage']['free_memory']) ||
				($status['memory_usage']['used_memory'] / $status['memory_usage']['free_memory'] > 9)
			) {
				$recommendations[] = $this->l10n->t('The OPcache buffer is nearly full. To assure that all scripts can be hold in cache, it is recommended to apply <code>opcache.memory_consumption</code> to your PHP configuration with a value higher than <code>%s</code>.', [($this->iniGetWrapper->getNumeric('opcache.memory_consumption') ?: 'currently')]);
			}

			if (
				// Do not recommend to raise the interned strings buffer size above a quarter of the total OPcache size
				($this->iniGetWrapper->getNumeric('opcache.interned_strings_buffer') < $this->iniGetWrapper->getNumeric('opcache.memory_consumption') / 4) &&
				(
					empty($status['interned_strings_usage']['free_memory']) ||
					($status['interned_strings_usage']['used_memory'] / $status['interned_strings_usage']['free_memory'] > 9)
				)
			) {
				$recommendations[] = $this->l10n->t('The OPcache interned strings buffer is nearly full. To assure that repeating strings can be effectively cached, it is recommended to apply <code>opcache.interned_strings_buffer</code> to your PHP configuration with a value higher than <code>%s</code>.', [($this->iniGetWrapper->getNumeric('opcache.interned_strings_buffer') ?: 'currently')]);
			}
		}

		return $recommendations;
	}

	/**
	 * Check if the required FreeType functions are present
	 * @return bool
	 */
	protected function hasFreeTypeSupport() {
		return function_exists('imagettfbbox') && function_exists('imagettftext');
	}

	protected function hasMissingIndexes(): array {
		$indexInfo = new MissingIndexInformation();
		// Dispatch event so apps can also hint for pending index updates if needed
		$event = new GenericEvent($indexInfo);
		$this->dispatcher->dispatch(IDBConnection::CHECK_MISSING_INDEXES_EVENT, $event);

		return $indexInfo->getListOfMissingIndexes();
	}

	protected function hasMissingPrimaryKeys(): array {
		$info = new MissingPrimaryKeyInformation();
		// Dispatch event so apps can also hint for pending index updates if needed
		$event = new GenericEvent($info);
		$this->dispatcher->dispatch(IDBConnection::CHECK_MISSING_PRIMARY_KEYS_EVENT, $event);

		return $info->getListOfMissingPrimaryKeys();
	}

	protected function hasMissingColumns(): array {
		$indexInfo = new MissingColumnInformation();
		// Dispatch event so apps can also hint for pending index updates if needed
		$event = new GenericEvent($indexInfo);
		$this->dispatcher->dispatch(IDBConnection::CHECK_MISSING_COLUMNS_EVENT, $event);

		return $indexInfo->getListOfMissingColumns();
	}

	protected function isSqliteUsed() {
		return strpos($this->config->getSystemValue('dbtype'), 'sqlite') !== false;
	}

	protected function isReadOnlyConfig(): bool {
		return \OC_Helper::isReadOnlyConfigEnabled();
	}

	protected function wasEmailTestSuccessful(): bool {
		// Handle the case that the configuration was set before the check was introduced or it was only set via command line and not from the UI
		if ($this->config->getAppValue('core', 'emailTestSuccessful', '') === '' && $this->config->getSystemValue('mail_domain', '') === '') {
			return false;
		}

		// The mail test was unsuccessful or the config was changed using the UI without verifying with a testmail, hence return false
		if ($this->config->getAppValue('core', 'emailTestSuccessful', '') === '0') {
			return false;
		}

		return true;
	}

	protected function hasValidTransactionIsolationLevel(): bool {
		try {
			if ($this->db->getDatabasePlatform() instanceof SqlitePlatform) {
				return true;
			}

			return $this->db->getTransactionIsolation() === TransactionIsolationLevel::READ_COMMITTED;
		} catch (Exception $e) {
			// ignore
		}

		return true;
	}

	protected function hasFileinfoInstalled(): bool {
		return \OC_Util::fileInfoLoaded();
	}

	protected function hasWorkingFileLocking(): bool {
		return !($this->lockingProvider instanceof NoopLockingProvider);
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
		$lastCronRun = $this->config->getAppValue('core', 'lastcron', 0);
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

	/**
	 * Checks for potential PHP modules that would improve the instance
	 *
	 * @return string[] A list of PHP modules that is recommended
	 */
	protected function hasRecommendedPHPModules(): array {
		$recommendedPHPModules = [];

		if (!extension_loaded('intl')) {
			$recommendedPHPModules[] = 'intl';
		}

		if (!extension_loaded('sysvsem')) {
			// used to limit the usage of resources by preview generator
			$recommendedPHPModules[] = 'sysvsem';
		}

		if (!defined('PASSWORD_ARGON2I')) {
			// Installing php-sodium on >=php7.4 will provide PASSWORD_ARGON2I
			// on previous version argon2 wasn't part of the "standard" extension
			// and RedHat disabled it so even installing php-sodium won't provide argon2i
			// support in password_hash/password_verify.
			$recommendedPHPModules[] = 'sodium';
		}

		return $recommendedPHPModules;
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

	protected function is64bit(): bool {
		if (PHP_INT_SIZE < 8) {
			return false;
		} else {
			return true;
		}
	}

	protected function isMysqlUsedWithoutUTF8MB4(): bool {
		return ($this->config->getSystemValue('dbtype', 'sqlite') === 'mysql') && ($this->config->getSystemValue('mysql.utf8mb4', false) === false);
	}

	protected function hasBigIntConversionPendingColumns(): array {
		// copy of ConvertFilecacheBigInt::getColumnsByTable()
		$tables = [
			'activity' => ['activity_id', 'object_id'],
			'activity_mq' => ['mail_id'],
			'authtoken' => ['id'],
			'bruteforce_attempts' => ['id'],
			'federated_reshares' => ['share_id'],
			'filecache' => ['fileid', 'storage', 'parent', 'mimetype', 'mimepart', 'mtime', 'storage_mtime'],
			'filecache_extended' => ['fileid'],
			'files_trash' => ['auto_id'],
			'file_locks' => ['id'],
			'file_metadata' => ['id'],
			'jobs' => ['id'],
			'mimetypes' => ['id'],
			'mounts' => ['id', 'storage_id', 'root_id', 'mount_id'],
			'share_external' => ['id', 'parent'],
			'storages' => ['numeric_id'],
		];

		$schema = new SchemaWrapper($this->db);
		$isSqlite = $this->db->getDatabasePlatform() instanceof SqlitePlatform;
		$pendingColumns = [];

		foreach ($tables as $tableName => $columns) {
			if (!$schema->hasTable($tableName)) {
				continue;
			}

			$table = $schema->getTable($tableName);
			foreach ($columns as $columnName) {
				$column = $table->getColumn($columnName);
				$isAutoIncrement = $column->getAutoincrement();
				$isAutoIncrementOnSqlite = $isSqlite && $isAutoIncrement;
				if ($column->getType()->getName() !== Types::BIGINT && !$isAutoIncrementOnSqlite) {
					$pendingColumns[] = $tableName . '.' . $columnName;
				}
			}
		}

		return $pendingColumns;
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
		$phpDefaultCharset = new PhpDefaultCharset();
		$phpOutputBuffering = new PhpOutputBuffering();
		$legacySSEKeyFormat = new LegacySSEKeyFormat($this->l10n, $this->config, $this->urlGenerator);
		$checkUserCertificates = new CheckUserCertificates($this->l10n, $this->config, $this->urlGenerator);
		$supportedDatabases = new SupportedDatabase($this->l10n, $this->connection);
		$ldapInvalidUuids = new LdapInvalidUuids($this->appManager, $this->l10n, $this->serverContainer);

		return new DataResponse(
			[
				'isGetenvServerWorking' => !empty(getenv('PATH')),
				'isReadOnlyConfig' => $this->isReadOnlyConfig(),
				'hasValidTransactionIsolationLevel' => $this->hasValidTransactionIsolationLevel(),
				'wasEmailTestSuccessful' => $this->wasEmailTestSuccessful(),
				'hasFileinfoInstalled' => $this->hasFileinfoInstalled(),
				'hasWorkingFileLocking' => $this->hasWorkingFileLocking(),
				'suggestedOverwriteCliURL' => $this->getSuggestedOverwriteCliURL(),
				'cronInfo' => $this->getLastCronInfo(),
				'cronErrors' => $this->getCronErrors(),
				'isFairUseOfFreePushService' => $this->isFairUseOfFreePushService(),
				'serverHasInternetConnectionProblems' => $this->hasInternetConnectivityProblems(),
				'isMemcacheConfigured' => $this->isMemcacheConfigured(),
				'memcacheDocs' => $this->urlGenerator->linkToDocs('admin-performance'),
				'isRandomnessSecure' => $this->isRandomnessSecure(),
				'securityDocs' => $this->urlGenerator->linkToDocs('admin-security'),
				'isUsedTlsLibOutdated' => $this->isUsedTlsLibOutdated(),
				'phpSupported' => $this->isPhpSupported(),
				'forwardedForHeadersWorking' => $this->forwardedForHeadersWorking(),
				'reverseProxyDocs' => $this->urlGenerator->linkToDocs('admin-reverse-proxy'),
				'isCorrectMemcachedPHPModuleInstalled' => $this->isCorrectMemcachedPHPModuleInstalled(),
				'hasPassedCodeIntegrityCheck' => $this->checker->hasPassedCheck(),
				'codeIntegrityCheckerDocumentation' => $this->urlGenerator->linkToDocs('admin-code-integrity'),
				'OpcacheSetupRecommendations' => $this->getOpcacheSetupRecommendations(),
				'isSettimelimitAvailable' => $this->isSettimelimitAvailable(),
				'hasFreeTypeSupport' => $this->hasFreeTypeSupport(),
				'missingPrimaryKeys' => $this->hasMissingPrimaryKeys(),
				'missingIndexes' => $this->hasMissingIndexes(),
				'missingColumns' => $this->hasMissingColumns(),
				'isSqliteUsed' => $this->isSqliteUsed(),
				'databaseConversionDocumentation' => $this->urlGenerator->linkToDocs('admin-db-conversion'),
				'isMemoryLimitSufficient' => $this->memoryInfo->isMemoryLimitSufficient(),
				'appDirsWithDifferentOwner' => $this->getAppDirsWithDifferentOwner(),
				'isImagickEnabled' => $this->isImagickEnabled(),
				'areWebauthnExtensionsEnabled' => $this->areWebauthnExtensionsEnabled(),
				'is64bit' => $this->is64bit(),
				'recommendedPHPModules' => $this->hasRecommendedPHPModules(),
				'pendingBigIntConversionColumns' => $this->hasBigIntConversionPendingColumns(),
				'isMysqlUsedWithoutUTF8MB4' => $this->isMysqlUsedWithoutUTF8MB4(),
				'isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed' => $this->isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed(),
				'reverseProxyGeneratedURL' => $this->urlGenerator->getAbsoluteURL('index.php'),
				'imageMagickLacksSVGSupport' => $this->imageMagickLacksSVGSupport(),
				PhpDefaultCharset::class => ['pass' => $phpDefaultCharset->run(), 'description' => $phpDefaultCharset->description(), 'severity' => $phpDefaultCharset->severity()],
				PhpOutputBuffering::class => ['pass' => $phpOutputBuffering->run(), 'description' => $phpOutputBuffering->description(), 'severity' => $phpOutputBuffering->severity()],
				LegacySSEKeyFormat::class => ['pass' => $legacySSEKeyFormat->run(), 'description' => $legacySSEKeyFormat->description(), 'severity' => $legacySSEKeyFormat->severity(), 'linkToDocumentation' => $legacySSEKeyFormat->linkToDocumentation()],
				CheckUserCertificates::class => ['pass' => $checkUserCertificates->run(), 'description' => $checkUserCertificates->description(), 'severity' => $checkUserCertificates->severity(), 'elements' => $checkUserCertificates->elements()],
				'isDefaultPhoneRegionSet' => $this->config->getSystemValueString('default_phone_region', '') !== '',
				SupportedDatabase::class => ['pass' => $supportedDatabases->run(), 'description' => $supportedDatabases->description(), 'severity' => $supportedDatabases->severity()],
				'temporaryDirectoryWritable' => $this->isTemporaryDirectoryWritable(),
				LdapInvalidUuids::class => ['pass' => $ldapInvalidUuids->run(), 'description' => $ldapInvalidUuids->description(), 'severity' => $ldapInvalidUuids->severity()],
			]
		);
	}
}
