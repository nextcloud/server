<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
use Exception;
use InvalidArgumentException;
use OC\Authentication\Token\PublicKeyTokenProvider;
use OC\Authentication\Token\TokenCleanupJob;
use OC\Core\BackgroundJobs\GenerateMetadataJob;
use OC\Log\Rotate;
use OC\Preview\BackgroundCleanupJob;
use OC\TextProcessing\RemoveOldTasksBackgroundJob;
use OC\User\BackgroundJobs\CleanupDeletedUsers;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Defaults;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Migration\IOutput;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;

class Setup {
	protected IL10N $l10n;

	public function __construct(
		protected SystemConfig $config,
		protected IniGetWrapper $iniWrapper,
		IL10NFactory $l10nFactory,
		protected Defaults $defaults,
		protected LoggerInterface $logger,
		protected ISecureRandom $random,
		protected Installer $installer,
	) {
		$this->l10n = $l10nFactory->get('lib');
	}

	protected static array $dbSetupClasses = [
		'mysql' => \OC\Setup\MySQL::class,
		'pgsql' => \OC\Setup\PostgreSQL::class,
		'oci' => \OC\Setup\OCI::class,
		'sqlite' => \OC\Setup\Sqlite::class,
		'sqlite3' => \OC\Setup\Sqlite::class,
	];

	/**
	 * Wrapper around the "class_exists" PHP function to be able to mock it
	 */
	protected function class_exists(string $name): bool {
		return class_exists($name);
	}

	/**
	 * Wrapper around the "is_callable" PHP function to be able to mock it
	 */
	protected function is_callable(string $name): bool {
		return is_callable($name);
	}

	/**
	 * Wrapper around \PDO::getAvailableDrivers
	 */
	protected function getAvailableDbDriversForPdo(): array {
		if (class_exists(\PDO::class)) {
			return \PDO::getAvailableDrivers();
		}
		return [];
	}

	/**
	 * Get the available and supported databases of this instance
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getSupportedDatabases(bool $allowAllDatabases = false): array {
		$availableDatabases = [
			'sqlite' => [
				'type' => 'pdo',
				'call' => 'sqlite',
				'name' => 'SQLite',
			],
			'mysql' => [
				'type' => 'pdo',
				'call' => 'mysql',
				'name' => 'MySQL/MariaDB',
			],
			'pgsql' => [
				'type' => 'pdo',
				'call' => 'pgsql',
				'name' => 'PostgreSQL',
			],
			'oci' => [
				'type' => 'function',
				'call' => 'oci_connect',
				'name' => 'Oracle',
			],
		];
		if ($allowAllDatabases) {
			$configuredDatabases = array_keys($availableDatabases);
		} else {
			$configuredDatabases = $this->config->getValue('supportedDatabases',
				['sqlite', 'mysql', 'pgsql']);
		}
		if (!is_array($configuredDatabases)) {
			throw new Exception('Supported databases are not properly configured.');
		}

		$supportedDatabases = [];

		foreach ($configuredDatabases as $database) {
			if (array_key_exists($database, $availableDatabases)) {
				$working = false;
				$type = $availableDatabases[$database]['type'];
				$call = $availableDatabases[$database]['call'];

				if ($type === 'function') {
					$working = $this->is_callable($call);
				} elseif ($type === 'pdo') {
					$working = in_array($call, $this->getAvailableDbDriversForPdo(), true);
				}
				if ($working) {
					$supportedDatabases[$database] = $availableDatabases[$database]['name'];
				}
			}
		}

		return $supportedDatabases;
	}

	/**
	 * Gathers system information like database type and does
	 * a few system checks.
	 *
	 * @return array of system info, including an "errors" value
	 *               in case of errors/warnings
	 */
	public function getSystemInfo(bool $allowAllDatabases = false): array {
		$databases = $this->getSupportedDatabases($allowAllDatabases);

		$dataDir = $this->config->getValue('datadirectory', \OC::$SERVERROOT . '/data');

		$errors = [];

		// Create data directory to test whether the .htaccess works
		// Notice that this is not necessarily the same data directory as the one
		// that will effectively be used.
		if (!file_exists($dataDir)) {
			@mkdir($dataDir);
		}
		$htAccessWorking = true;
		if (is_dir($dataDir) && is_writable($dataDir)) {
			// Protect data directory here, so we can test if the protection is working
			self::protectDataDirectory();

			try {
				$htAccessWorking = $this->isHtaccessWorking($dataDir);
			} catch (\OCP\HintException $e) {
				$errors[] = [
					'error' => $e->getMessage(),
					'exception' => $e,
					'hint' => $e->getHint(),
				];
				$htAccessWorking = false;
			}
		}

		if (\OC_Util::runningOnMac()) {
			$errors[] = [
				'error' => $this->l10n->t(
					'Mac OS X is not supported and %s will not work properly on this platform. ' .
					'Use it at your own risk!',
					[$this->defaults->getProductName()]
				),
				'hint' => $this->l10n->t('For the best results, please consider using a GNU/Linux server instead.'),
			];
		}

		if ($this->iniWrapper->getString('open_basedir') !== '' && PHP_INT_SIZE === 4) {
			$errors[] = [
				'error' => $this->l10n->t(
					'It seems that this %s instance is running on a 32-bit PHP environment and the open_basedir has been configured in php.ini. ' .
					'This will lead to problems with files over 4 GB and is highly discouraged.',
					[$this->defaults->getProductName()]
				),
				'hint' => $this->l10n->t('Please remove the open_basedir setting within your php.ini or switch to 64-bit PHP.'),
			];
		}

		return [
			'databases' => $databases,
			'directory' => $dataDir,
			'htaccessWorking' => $htAccessWorking,
			'errors' => $errors,
		];
	}

	public function createHtaccessTestFile(string $dataDir): string|false {
		// php dev server does not support htaccess
		if (php_sapi_name() === 'cli-server') {
			return false;
		}

		// testdata
		$fileName = '/htaccesstest.txt';
		$testContent = 'This is used for testing whether htaccess is properly enabled to disallow access from the outside. This file can be safely removed.';

		// creating a test file
		$testFile = $dataDir . '/' . $fileName;

		if (file_exists($testFile)) {// already running this test, possible recursive call
			return false;
		}

		$fp = @fopen($testFile, 'w');
		if (!$fp) {
			throw new \OCP\HintException('Can\'t create test file to check for working .htaccess file.',
				'Make sure it is possible for the web server to write to ' . $testFile);
		}
		fwrite($fp, $testContent);
		fclose($fp);

		return $testContent;
	}

	/**
	 * Check if the .htaccess file is working
	 *
	 * @param \OCP\IConfig $config
	 * @return bool
	 * @throws Exception
	 * @throws \OCP\HintException If the test file can't get written.
	 */
	public function isHtaccessWorking(string $dataDir) {
		$config = Server::get(IConfig::class);

		if (\OC::$CLI || !$config->getSystemValueBool('check_for_working_htaccess', true)) {
			return true;
		}

		$testContent = $this->createHtaccessTestFile($dataDir);
		if ($testContent === false) {
			return false;
		}

		$fileName = '/htaccesstest.txt';
		$testFile = $dataDir . '/' . $fileName;

		// accessing the file via http
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL(\OC::$WEBROOT . '/data' . $fileName);
		try {
			$content = Server::get(IClientService::class)->newClient()->get($url)->getBody();
		} catch (\Exception $e) {
			$content = false;
		}

		if (str_starts_with($url, 'https:')) {
			$url = 'http:' . substr($url, 6);
		} else {
			$url = 'https:' . substr($url, 5);
		}

		try {
			$fallbackContent = Server::get(IClientService::class)->newClient()->get($url)->getBody();
		} catch (\Exception $e) {
			$fallbackContent = false;
		}

		// cleanup
		@unlink($testFile);

		/*
		 * If the content is not equal to test content our .htaccess
		 * is working as required
		 */
		return $content !== $testContent && $fallbackContent !== $testContent;
	}

	/**
	 * @return array<string|array> errors
	 */
	public function install(array $options, ?IOutput $output = null): array {
		$l = $this->l10n;

		$error = [];
		$dbType = $options['dbtype'];

		$disableAdminUser = (bool)($options['admindisable'] ?? false);

		if (!$disableAdminUser) {
			if (empty($options['adminlogin'])) {
				$error[] = $l->t('Set an admin Login.');
			}
			if (empty($options['adminpass'])) {
				$error[] = $l->t('Set an admin password.');
			}
		}
		if (empty($options['directory'])) {
			$options['directory'] = \OC::$SERVERROOT . '/data';
		}

		if (!isset(self::$dbSetupClasses[$dbType])) {
			$dbType = 'sqlite';
		}

		$dataDir = htmlspecialchars_decode($options['directory']);

		$class = self::$dbSetupClasses[$dbType];
		/** @var \OC\Setup\AbstractDatabase $dbSetup */
		$dbSetup = new $class($l, $this->config, $this->logger, $this->random);
		$error = array_merge($error, $dbSetup->validate($options));

		// validate the data directory
		if ((!is_dir($dataDir) && !mkdir($dataDir)) || !is_writable($dataDir)) {
			$error[] = $l->t('Cannot create or write into the data directory %s', [$dataDir]);
		}

		if (!empty($error)) {
			return $error;
		}

		$request = Server::get(IRequest::class);

		//no errors, good
		if (isset($options['trusted_domains'])
			&& is_array($options['trusted_domains'])) {
			$trustedDomains = $options['trusted_domains'];
		} else {
			$trustedDomains = [$request->getInsecureServerHost()];
		}

		//use sqlite3 when available, otherwise sqlite2 will be used.
		if ($dbType === 'sqlite' && class_exists('SQLite3')) {
			$dbType = 'sqlite3';
		}

		//generate a random salt that is used to salt the local  passwords
		$salt = $this->random->generate(30);
		// generate a secret
		$secret = $this->random->generate(48);

		//write the config file
		$newConfigValues = [
			'passwordsalt' => $salt,
			'secret' => $secret,
			'trusted_domains' => $trustedDomains,
			'datadirectory' => $dataDir,
			'dbtype' => $dbType,
			'version' => implode('.', \OCP\Util::getVersion()),
		];

		if ($this->config->getValue('overwrite.cli.url', null) === null) {
			$newConfigValues['overwrite.cli.url'] = $request->getServerProtocol() . '://' . $request->getInsecureServerHost() . \OC::$WEBROOT;
		}

		$this->config->setValues($newConfigValues);

		$this->outputDebug($output, 'Configuring database');
		$dbSetup->initialize($options);
		try {
			$dbSetup->setupDatabase();
		} catch (\OC\DatabaseSetupException $e) {
			$error[] = [
				'error' => $e->getMessage(),
				'exception' => $e,
				'hint' => $e->getHint(),
			];
			return $error;
		} catch (Exception $e) {
			$error[] = [
				'error' => 'Error while trying to create admin account: ' . $e->getMessage(),
				'exception' => $e,
				'hint' => '',
			];
			return $error;
		}

		$this->outputDebug($output, 'Run server migrations');
		try {
			// apply necessary migrations
			$dbSetup->runMigrations($output);
		} catch (Exception $e) {
			$error[] = [
				'error' => 'Error while trying to initialise the database: ' . $e->getMessage(),
				'exception' => $e,
				'hint' => '',
			];
			return $error;
		}

		$user = null;
		if (!$disableAdminUser) {
			$username = htmlspecialchars_decode($options['adminlogin']);
			$password = htmlspecialchars_decode($options['adminpass']);
			$this->outputDebug($output, 'Create admin account');

			try {
				$user = Server::get(IUserManager::class)->createUser($username, $password);
				if (!$user) {
					$error[] = "Account <$username> could not be created.";
					return $error;
				}
			} catch (Exception $exception) {
				$error[] = $exception->getMessage();
				return $error;
			}
		}

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'installedat', (string)microtime(true));
		$appConfig = Server::get(IAppConfig::class);
		$appConfig->setValueInt('core', 'lastupdatedat', time());

		$vendorData = $this->getVendorData();
		$config->setAppValue('core', 'vendor', $vendorData['vendor']);
		if ($vendorData['channel'] !== 'stable') {
			$config->setSystemValue('updater.release.channel', $vendorData['channel']);
		}

		$group = Server::get(IGroupManager::class)->createGroup('admin');
		if ($user !== null && $group instanceof IGroup) {
			$group->addUser($user);
		}

		// Install shipped apps and specified app bundles
		$this->outputDebug($output, 'Install default apps');
		Installer::installShippedApps(false, $output);

		// create empty file in data dir, so we can later find
		// out that this is indeed a Nextcloud data directory
		$this->outputDebug($output, 'Setup data directory');
		file_put_contents(
			$config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/.ncdata',
			"# Nextcloud data directory\n# Do not change this file",
		);

		// Update .htaccess files
		self::updateHtaccess();
		self::protectDataDirectory();

		$this->outputDebug($output, 'Install background jobs');
		self::installBackgroundJobs();

		//and we are done
		$config->setSystemValue('installed', true);
		if (self::shouldRemoveCanInstallFile()) {
			unlink(\OC::$configDir . '/CAN_INSTALL');
		}

		$bootstrapCoordinator = Server::get(\OC\AppFramework\Bootstrap\Coordinator::class);
		$bootstrapCoordinator->runInitialRegistration();

		if (!$disableAdminUser) {
			// Create a session token for the newly created user
			// The token provider requires a working db, so it's not injected on setup
			/** @var \OC\User\Session $userSession */
			$userSession = Server::get(IUserSession::class);
			$provider = Server::get(PublicKeyTokenProvider::class);
			$userSession->setTokenProvider($provider);
			$userSession->login($username, $password);
			$user = $userSession->getUser();
			if (!$user) {
				$error[] = 'No account found in session.';
				return $error;
			}
			$userSession->createSessionToken($request, $user->getUID(), $username, $password);

			$session = $userSession->getSession();
			$session->set('last-password-confirm', Server::get(ITimeFactory::class)->getTime());

			// Set email for admin
			if (!empty($options['adminemail'])) {
				$user->setSystemEMailAddress($options['adminemail']);
			}
		}

		return $error;
	}

	public static function installBackgroundJobs(): void {
		$jobList = Server::get(IJobList::class);
		$jobList->add(TokenCleanupJob::class);
		$jobList->add(Rotate::class);
		$jobList->add(BackgroundCleanupJob::class);
		$jobList->add(RemoveOldTasksBackgroundJob::class);
		$jobList->add(CleanupDeletedUsers::class);
		$jobList->add(GenerateMetadataJob::class);
	}

	/**
	 * @return string Absolute path to htaccess
	 */
	private function pathToHtaccess(): string {
		return \OC::$SERVERROOT . '/.htaccess';
	}

	/**
	 * Find webroot from config
	 *
	 * @throws InvalidArgumentException when invalid value for overwrite.cli.url
	 */
	private static function findWebRoot(SystemConfig $config): string {
		// For CLI read the value from overwrite.cli.url
		if (\OC::$CLI) {
			$webRoot = $config->getValue('overwrite.cli.url', '');
			if ($webRoot === '') {
				throw new InvalidArgumentException('overwrite.cli.url is empty');
			}
			if (!filter_var($webRoot, FILTER_VALIDATE_URL)) {
				throw new InvalidArgumentException('invalid value for overwrite.cli.url');
			}
			$webRoot = rtrim((parse_url($webRoot, PHP_URL_PATH) ?? ''), '/');
		} else {
			$webRoot = !empty(\OC::$WEBROOT) ? \OC::$WEBROOT : '/';
		}

		return $webRoot;
	}

	/**
	 * Append the correct ErrorDocument path for Apache hosts
	 *
	 * @return bool True when success, False otherwise
	 * @throws \OCP\AppFramework\QueryException
	 */
	public static function updateHtaccess(): bool {
		$config = Server::get(SystemConfig::class);

		try {
			$webRoot = self::findWebRoot($config);
		} catch (InvalidArgumentException $e) {
			return false;
		}

		$setupHelper = Server::get(\OC\Setup::class);

		if (!is_writable($setupHelper->pathToHtaccess())) {
			return false;
		}

		$htaccessContent = file_get_contents($setupHelper->pathToHtaccess());
		$content = "#### DO NOT CHANGE ANYTHING ABOVE THIS LINE ####\n";
		$htaccessContent = explode($content, $htaccessContent, 2)[0];

		//custom 403 error page
		$content .= "\nErrorDocument 403 " . $webRoot . '/index.php/error/403';

		//custom 404 error page
		$content .= "\nErrorDocument 404 " . $webRoot . '/index.php/error/404';

		// Add rewrite rules if the RewriteBase is configured
		$rewriteBase = $config->getValue('htaccess.RewriteBase', '');
		if ($rewriteBase !== '') {
			$content .= "\n<IfModule mod_rewrite.c>";
			$content .= "\n  Options -MultiViews";
			$content .= "\n  RewriteRule ^core/js/oc.js$ index.php [PT,E=PATH_INFO:$1]";
			$content .= "\n  RewriteRule ^core/preview.png$ index.php [PT,E=PATH_INFO:$1]";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !\\.(css|js|mjs|svg|gif|ico|jpg|jpeg|png|webp|html|otf|ttf|woff2?|map|webm|mp4|mp3|ogg|wav|flac|wasm|tflite)$";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/core/ajax/update\\.php";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/core/img/(favicon\\.ico|manifest\\.json)$";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/(cron|public|remote|status)\\.php";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/ocs/v(1|2)\\.php";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/robots\\.txt";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/(ocs-provider|updater)/";
			$content .= "\n  RewriteCond %{REQUEST_URI} !^/\\.well-known/(acme-challenge|pki-validation)/.*";
			$content .= "\n  RewriteCond %{REQUEST_FILENAME} !/richdocumentscode(_arm64)?/proxy.php$";
			$content .= "\n  RewriteRule . index.php [PT,E=PATH_INFO:$1]";
			$content .= "\n  RewriteBase " . $rewriteBase;
			$content .= "\n  <IfModule mod_env.c>";
			$content .= "\n    SetEnv front_controller_active true";
			$content .= "\n    <IfModule mod_dir.c>";
			$content .= "\n      DirectorySlash off";
			$content .= "\n    </IfModule>";
			$content .= "\n  </IfModule>";
			$content .= "\n</IfModule>";
		}

		// Never write file back if disk space should be too low
		if (function_exists('disk_free_space')) {
			$df = disk_free_space(\OC::$SERVERROOT);
			$size = strlen($content) + 10240;
			if ($df !== false && $df < (float)$size) {
				throw new \Exception(\OC::$SERVERROOT . ' does not have enough space for writing the htaccess file! Not writing it back!');
			}
		}
		//suppress errors in case we don't have permissions for it
		return (bool)@file_put_contents($setupHelper->pathToHtaccess(), $htaccessContent . $content . "\n");
	}

	public static function protectDataDirectory(): void {
		//Require all denied
		$now = date('Y-m-d H:i:s');
		$content = "# Generated by Nextcloud on $now\n";
		$content .= "# Section for Apache 2.4 to 2.6\n";
		$content .= "<IfModule mod_authz_core.c>\n";
		$content .= "  Require all denied\n";
		$content .= "</IfModule>\n";
		$content .= "<IfModule mod_access_compat.c>\n";
		$content .= "  Order Allow,Deny\n";
		$content .= "  Deny from all\n";
		$content .= "  Satisfy All\n";
		$content .= "</IfModule>\n\n";
		$content .= "# Section for Apache 2.2\n";
		$content .= "<IfModule !mod_authz_core.c>\n";
		$content .= "  <IfModule !mod_access_compat.c>\n";
		$content .= "    <IfModule mod_authz_host.c>\n";
		$content .= "      Order Allow,Deny\n";
		$content .= "      Deny from all\n";
		$content .= "    </IfModule>\n";
		$content .= "    Satisfy All\n";
		$content .= "  </IfModule>\n";
		$content .= "</IfModule>\n\n";
		$content .= "# Section for Apache 2.2 to 2.6\n";
		$content .= "<IfModule mod_autoindex.c>\n";
		$content .= "  IndexIgnore *\n";
		$content .= '</IfModule>';

		$baseDir = Server::get(IConfig::class)->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data');
		file_put_contents($baseDir . '/.htaccess', $content);
		file_put_contents($baseDir . '/index.html', '');
	}

	private function getVendorData(): array {
		// this should really be a JSON file
		require \OC::$SERVERROOT . '/version.php';
		/** @var mixed $vendor */
		/** @var mixed $OC_Channel */
		return [
			'vendor' => (string)$vendor,
			'channel' => (string)$OC_Channel,
		];
	}

	public function shouldRemoveCanInstallFile(): bool {
		return Server::get(ServerVersion::class)->getChannel() !== 'git' && is_file(\OC::$configDir . '/CAN_INSTALL');
	}

	public function canInstallFileExists(): bool {
		return is_file(\OC::$configDir . '/CAN_INSTALL');
	}

	protected function outputDebug(?IOutput $output, string $message): void {
		if ($output instanceof IOutput) {
			$output->debug($message);
		}
	}
}
