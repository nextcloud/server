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
use OC\Core\BackgroundJobs\PreviewMigrationJob;
use OC\Log\Rotate;
use OC\Preview\BackgroundCleanupJob;
use OC\TextProcessing\RemoveOldTasksBackgroundJob;
use OC\User\BackgroundJobs\CleanupDeletedUsers;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Install\Events\InstallationCompletedEvent;
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
	public const MIN_PASSWORD_SALT_LENGTH = 30;
	public const MIN_SECRET_LENGTH = 48;

	protected IL10N $l10n;

	public function __construct(
		protected SystemConfig $config,
		protected IniGetWrapper $iniWrapper,
		IL10NFactory $l10nFactory,
		protected Defaults $defaults,
		protected LoggerInterface $logger,
		protected ISecureRandom $random,
		protected Installer $installer,
		protected IEventDispatcher $eventDispatcher,
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

		// Check if running directly on macOS (note: Linux containers on macOS will not trigger this)
		if (!getenv('CI') && PHP_OS_FAMILY === 'Darwin') {
			$errors[] = [
				'error' => $this->l10n->t(
					'macOS is not supported and %s will not work properly on this platform.',
					[$this->defaults->getProductName()]
				),
				'hint' => $this->l10n->t('For the best results, please consider using a GNU/Linux server instead.'),
			];
		}

		if ($this->iniWrapper->getString('open_basedir') !== '' && PHP_INT_SIZE === 4) {
			$errors[] = [
				'error' => $this->l10n->t(
					'It seems that this %s instance is running on a 32-bit PHP environment and the open_basedir has been configured in php.ini. '
					. 'This will lead to problems with files over 4 GB and is highly discouraged.',
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

	/**
	 * Create a temporary htaccess test file for isHtaccessWorking().
	 *
	 * Writes "htaccesstest.txt" into $dataDir and returns its content, or false if skipped.
	 *
	 * @return string|false The test content written, or false if the test was skipped
	 * @throws \OCP\HintException If the test file cannot be created or written
	 * @internal
	 */
	private function createHtaccessTestFile(string $dataDir): string|false {
		$testFile = $dataDir . '/htaccesstest.txt';
		if (file_exists($testFile)) { // unexpected; possible recursive call
			return false;
		}

		$testContent = 'This is used for testing whether htaccess is properly enabled to disallow access from the outside. This file can be safely removed.';
		$written = @file_put_contents($testFile, $testContent);
		if ($written === false) {
			throw new \OCP\HintException(
				'Can\'t create htaccess test file to verify .htaccess protection.',
				'Make sure the web server user can write to the data directory (default: /data).'
			);
		}

		return $testContent;
	}

	/**
	 * Check whether the .htaccess protection is effective for the given data directory.
	 *
	 * Creates a temporary file (htaccesstest.txt) under $dataDir and performs an HTTP
	 * probe. Bypassed under some scenarios (see code) when unnecessary or to avoid false
	 * negatives.
	 *
	 * @return bool True when .htaccess protection appears to work, false otherwise.
	 * @throws \OCP\HintException If the test file cannot be created.
	 */
	public function isHtaccessWorking(string $dataDir): bool {

		// Skip quietly to avoid false negatives since web server state unknown in CLI mode
		if (\OC::$CLI) {
			return true;
		}

		// Skip quietly if explicitly configured to do so
		if (!(bool)$this->config->getValue('check_for_working_htaccess', true)) {
			return true;
		}

		// Don't bother probing; we already know PHP's dev server does not support
		if (PHP_SAPI === 'cli-server') {
			return false;
		}

		// Create a temporary htaccess test file
		$testContent = $this->createHtaccessTestFile($dataDir);
		if ($testContent === false) { // File already exists for some reason
			// Note: createHtaccessTestFile() passes up a HintException for most real-world
			// failure scenarios which we currently expect our caller to handle.
			return false;
		}

		$testFile = $dataDir . '/htaccesstest.txt';

		// TODO: consider supporting non-default datadirectory
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL(\OC::$WEBROOT . '/data/htaccesstest.txt');

		$client = Server::get(IClientService::class)->newClient();
		$fetch = function (string $target) use ($client, $testContent): string|false {
			try {
				$resp = $client->get($target);
				$body = $resp->getBody();

				if (is_resource($body)) {
					$max = strlen($testContent) + 1024; // small margin
					return stream_get_contents($body, $max);
				}

				return (string)$body;
			} catch (\Exception $e) {
				return false;
			}
		};

		try {
			$content = $fetch($url);
			// Probe both schemes for full coverage
			$fallbackUrl = str_starts_with($url, 'https:') ? 'http:' . substr($url, 6) : 'https:' . substr($url, 5);
			$fallbackContent = $fetch($fallbackUrl);

			// .htaccess likely works if content of probes !== the test content
			return $content !== $testContent && $fallbackContent !== $testContent;
		} finally {
			// Always cleanup
			@unlink($testFile);
		}
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

		$salt = $options['passwordsalt'] ?: $this->random->generate(self::MIN_PASSWORD_SALT_LENGTH);
		$secret = $options['secret'] ?: $this->random->generate(self::MIN_SECRET_LENGTH);

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
		$installer = Server::get(Installer::class);
		$installer->installShippedApps(false, $output);

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

		// Dispatch installation completed event
		$adminUsername = !$disableAdminUser ? ($options['adminlogin'] ?? null) : null;
		$adminEmail = !empty($options['adminemail']) ? $options['adminemail'] : null;
		$this->eventDispatcher->dispatchTyped(
			new InstallationCompletedEvent($dataDir, $adminUsername, $adminEmail)
		);

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
		$jobList->add(PreviewMigrationJob::class);
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
	 * Update the default (installation provided) .htaccess by inserting or overwriting
	 * the non-static section (ErrorDocument and optional front end controller) while
	 * preserving all static (install artifact) content above the preservation marker.
	 *
	 * Runs regardless of web server in use, but only effective on Apache web servers.
	 *
	 * TODO: Make this no longer static (looks easy; few calls)
	 *
	 * @return bool True on success; False if not
	 */
	public static function updateHtaccess(): bool {
		$setupHelper = Server::get(\OC\Setup::class);
		$htaccessPath = $setupHelper->pathToHtaccess();

		// The distributed .htaccess file is required
		if (!is_writable($htaccessPath)
			|| !is_readable($htaccessPath)
		) {
			// cannot update .htaccess (bad permissions or it is missing)
			return false;
		}

		// We're a static method; cannot use $this->config
		$config = Server::get(SystemConfig::class);

		try {
			$webRoot = self::findWebRoot($config);
		} catch (InvalidArgumentException $e) {
			return false;
		}

		// TODO: Add a check to detect when the .htaccess file isn't the expected one 
		// (e.g. when it's the datadirectory one due to a misconfiguration) so that we
		// don't append to the wrong file (and enable a very problematic configuration).

		// Read original content
		$original = @file_get_contents($htaccessPath);
		// extra check for good measure
		if ($original === false) {
			// bad permissions or installation provided .htaccess is missing
			return false;
		}

		$preservationBoundary = "#### DO NOT CHANGE ANYTHING ABOVE THIS LINE ####\n";

		// Preserve everything above the boundary line; drop the rest (if any)
		$parts = explode($preservationBoundary, $original, 2);
		$preservedContent = $parts[0];

		// New section must start with the boundary marker
		$newContent = $preservationBoundary;

		// Handle 403s/404s via primary front controller under all installation scenarios
		// ErrorDocument path must be relative to the VirtualHost DocumentRoot
		$newContent .= "\nErrorDocument 403 " . $webRoot . '/index.php/error/403';
		$newContent .= "\nErrorDocument 404 " . $webRoot . '/index.php/error/404';

		// RewriteBase tells mod_rewrite the URL base for the rules in this
		// .htaccess file. It is required when Nextcloud is served from a subpath (so the
		// rewrite rules generate and match the correct prefixed request paths). It
		// also enables "pretty" URLs by routing most requests to the primary front
		// controller (index.php).
		//
		// When served from the document root, RewriteBase is usually not required,
		// though some specific server setups may still need it. In Nextcloud, setting
		// htaccess.RewriteBase to '/' (instead of leaving it empty or unconfigured) is
		// the trigger that causes updateHtaccess() to write the bundled rewrite rules
		// and thus enable "pretty" URLs for root installs.

		$rewriteBase = $config->getValue('htaccess.RewriteBase', '');
		// Notes:
		//  - Equivalent handling may be provided by the web server (e.g. nginx location
		//	  / Apache vhost blocks) even without this.
		//  - This is not the entire Nextcloud .htaccess file; these are merely appended
		//	  to the base file distributed with each release.
		// TODO: Document these rules/conditions
		if ($rewriteBase !== '') {
			$newContent .= "\n<IfModule mod_rewrite.c>";
			$newContent .= "\n  Options -MultiViews";
			$newContent .= "\n  RewriteRule ^core/js/oc.js$ index.php [PT,E=PATH_INFO:$1]";
			$newContent .= "\n  RewriteRule ^core/preview.png$ index.php [PT,E=PATH_INFO:$1]";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !\\.(css|js|mjs|svg|gif|ico|jpg|jpeg|png|webp|html|otf|ttf|woff2?|map|webm|mp4|mp3|ogg|wav|flac|wasm|tflite)$";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/core/ajax/update\\.php";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/core/img/(favicon\\.ico|manifest\\.json)$";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/(cron|public|remote|status)\\.php";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/ocs/v(1|2)\\.php";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/robots\\.txt";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/(ocs-provider|updater)/";
			$newContent .= "\n  RewriteCond %{REQUEST_URI} !^/\\.well-known/(acme-challenge|pki-validation)/.*";
			$newContent .= "\n  RewriteCond %{REQUEST_FILENAME} !/richdocumentscode(_arm64)?/proxy.php$";
			$newContent .= "\n  RewriteRule . index.php [PT,E=PATH_INFO:$1]";
			$newContent .= "\n  RewriteBase " . $rewriteBase;
			$newContent .= "\n  <IfModule mod_env.c>";
			$newContent .= "\n    SetEnv front_controller_active true";
			$newContent .= "\n    <IfModule mod_dir.c>";
			$newContent .= "\n      DirectorySlash off";
			$newContent .= "\n    </IfModule>";
			$newContent .= "\n  </IfModule>";
			$newContent .= "\n</IfModule>";
		}

		// Assemble new file contents
		$assembled = $preservedContent . $newContent . "\n";

		// Only write if changed
		if ($original !== $assembled) {
			// Guard against disk space being too low to safely update
			if (function_exists('disk_free_space')) {
				$df = disk_free_space(\OC::$SERVERROOT);
				$size = strlen($assembled) + 10240;
				if ($df !== false && $df < (float)$size) {
					throw new \Exception(\OC::$SERVERROOT . ' does not have enough storage space for writing the updated .htaccess file! Giving up!');
				}
			}
			// TODO: Consider atomic write (write to tmp + rename)
			$written = @file_put_contents($htaccessPath, $assembled);
			return ($written !== false);
		}

		return true;
	}

	/**
	 * Prevents direct HTTP access to user files (high security risk if the
	 * data directory were web-accessible).
	 *
	 * - Prevents directory listing of the data directory.
	 * - Provides a safe default protection for Apache installs (where .htaccess is honored).
	 */
	public static function protectDataDirectory(): void {

		$defaultDataDir = \OC::$SERVERROOT . '/data';
		$dataDir = Server::get(IConfig::class)->getSystemValueString('datadirectory', $defaultDataDir);

		// Ensure data directory exists and is writable
		if (!is_dir($dataDir) || !is_writable($dataDir)) {
			throw new \Exception("Unable to write to data directory ($dataDir) to protect it! Giving up!");
		}

		$dataDirHtaccess = $dataDir . '/.htaccess';
		$dataDirIndex = $dataDir . '/index.html';

		// Content for the .htaccess file that locks down (most) Apache environments
		$now = date('Y-m-d H:i:s');
		$content = "# Generated by Nextcloud on $now\n";
		$content .= "# Deployed in Nextcloud data directory\n";
		$content .= "# Do not change this file\n\n";
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

		// Create an empty index.html to prevent simply browsing
		$writtenIndex = file_put_contents($dataDirIndex, '');
		// Create the .htaccess file
		$writtenHtaccess = file_put_contents($dataDirHtaccess, $content);

		if ($writtenHtaccess === false || $writtenIndex === false) {
			throw new \Exception("Failed to write $dataDirHtaccess or $dataDirIndex");
		}
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
