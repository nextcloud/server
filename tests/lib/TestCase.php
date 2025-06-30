<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use DOMDocument;
use DOMNode;
use OC\Command\QueueBus;
use OC\Files\Cache\Storage;
use OC\Files\Config\MountProviderCollection;
use OC\Files\Filesystem;
use OC\Files\Mount\CacheMountProvider;
use OC\Files\Mount\LocalHomeMountProvider;
use OC\Files\Mount\RootMountProvider;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\SetupManager;
use OC\Template\Base;
use OCP\Command\IBus;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Security\ISecureRandom;
use OCP\Server;

if (version_compare(\PHPUnit\Runner\Version::id(), 10, '>=')) {
	trait OnNotSuccessfulTestTrait {
		protected function onNotSuccessfulTest(\Throwable $t): never {
			$this->restoreAllServices();

			// restore database connection
			if (!$this->IsDatabaseAccessAllowed()) {
				\OC::$server->registerService(IDBConnection::class, function () {
					return self::$realDatabase;
				});
			}

			parent::onNotSuccessfulTest($t);
		}
	}
} else {
	trait OnNotSuccessfulTestTrait {
		protected function onNotSuccessfulTest(\Throwable $t): void {
			$this->restoreAllServices();

			// restore database connection
			if (!$this->IsDatabaseAccessAllowed()) {
				\OC::$server->registerService(IDBConnection::class, function () {
					return self::$realDatabase;
				});
			}

			parent::onNotSuccessfulTest($t);
		}
	}
}

abstract class TestCase extends \PHPUnit\Framework\TestCase {
	/** @var \OC\Command\QueueBus */
	private $commandBus;

	/** @var IDBConnection */
	protected static $realDatabase = null;

	/** @var bool */
	private static $wasDatabaseAllowed = false;

	/** @var array */
	protected $services = [];

	use OnNotSuccessfulTestTrait;

	/**
	 * @param string $name
	 * @param mixed $newService
	 * @return bool
	 */
	public function overwriteService(string $name, $newService): bool {
		if (isset($this->services[$name])) {
			return false;
		}

		$this->services[$name] = Server::get($name);
		$container = \OC::$server->getAppContainerForService($name);
		$container = $container ?? \OC::$server;

		$container->registerService($name, function () use ($newService) {
			return $newService;
		});

		return true;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function restoreService(string $name): bool {
		if (isset($this->services[$name])) {
			$oldService = $this->services[$name];

			$container = \OC::$server->getAppContainerForService($name);
			$container = $container ?? \OC::$server;

			$container->registerService($name, function () use ($oldService) {
				return $oldService;
			});


			unset($this->services[$name]);
			return true;
		}

		return false;
	}

	public function restoreAllServices() {
		if (!empty($this->services)) {
			if (!empty($this->services)) {
				foreach ($this->services as $name => $service) {
					$this->restoreService($name);
				}
			}
		}
	}

	protected function getTestTraits() {
		$traits = [];
		$class = $this;
		do {
			$traits = array_merge(class_uses($class), $traits);
		} while ($class = get_parent_class($class));
		foreach ($traits as $trait => $same) {
			$traits = array_merge(class_uses($trait), $traits);
		}
		$traits = array_unique($traits);
		return array_filter($traits, function ($trait) {
			return substr($trait, 0, 5) === 'Test\\';
		});
	}

	protected function setUp(): void {
		// overwrite the command bus with one we can run ourselves
		$this->commandBus = new QueueBus();
		$this->overwriteService('AsyncCommandBus', $this->commandBus);
		$this->overwriteService(IBus::class, $this->commandBus);

		// detect database access
		self::$wasDatabaseAllowed = true;
		if (!$this->IsDatabaseAccessAllowed()) {
			self::$wasDatabaseAllowed = false;
			if (is_null(self::$realDatabase)) {
				self::$realDatabase = Server::get(IDBConnection::class);
			}
			\OC::$server->registerService(IDBConnection::class, function (): void {
				$this->fail('Your test case is not allowed to access the database.');
			});
		}

		$traits = $this->getTestTraits();
		foreach ($traits as $trait) {
			$methodName = 'setUp' . basename(str_replace('\\', '/', $trait));
			if (method_exists($this, $methodName)) {
				call_user_func([$this, $methodName]);
			}
		}
	}

	protected function tearDown(): void {
		$this->restoreAllServices();

		// restore database connection
		if (!$this->IsDatabaseAccessAllowed()) {
			\OC::$server->registerService(IDBConnection::class, function () {
				return self::$realDatabase;
			});
		}

		// further cleanup
		$hookExceptions = \OC_Hook::$thrownExceptions;
		\OC_Hook::$thrownExceptions = [];
		Server::get(ILockingProvider::class)->releaseAll();
		if (!empty($hookExceptions)) {
			throw $hookExceptions[0];
		}

		// fail hard if xml errors have not been cleaned up
		$errors = libxml_get_errors();
		libxml_clear_errors();
		if (!empty($errors)) {
			self::assertEquals([], $errors, 'There have been xml parsing errors');
		}

		if ($this->IsDatabaseAccessAllowed()) {
			Storage::getGlobalCache()->clearCache();
		}

		// tearDown the traits
		$traits = $this->getTestTraits();
		foreach ($traits as $trait) {
			$methodName = 'tearDown' . basename(str_replace('\\', '/', $trait));
			if (method_exists($this, $methodName)) {
				call_user_func([$this, $methodName]);
			}
		}
	}

	/**
	 * Allows us to test private methods/properties
	 *
	 * @param $object
	 * @param $methodName
	 * @param array $parameters
	 * @return mixed
	 */
	protected static function invokePrivate($object, $methodName, array $parameters = []) {
		if (is_string($object)) {
			$className = $object;
		} else {
			$className = get_class($object);
		}
		$reflection = new \ReflectionClass($className);

		if ($reflection->hasMethod($methodName)) {
			$method = $reflection->getMethod($methodName);

			$method->setAccessible(true);

			return $method->invokeArgs($object, $parameters);
		} elseif ($reflection->hasProperty($methodName)) {
			$property = $reflection->getProperty($methodName);

			$property->setAccessible(true);

			if (!empty($parameters)) {
				if ($property->isStatic()) {
					$property->setValue(null, array_pop($parameters));
				} else {
					$property->setValue($object, array_pop($parameters));
				}
			}

			if (is_object($object)) {
				return $property->getValue($object);
			}

			return $property->getValue();
		} elseif ($reflection->hasConstant($methodName)) {
			return $reflection->getConstant($methodName);
		}

		return false;
	}

	/**
	 * Returns a unique identifier as uniqid() is not reliable sometimes
	 *
	 * @param string $prefix
	 * @param int $length
	 * @return string
	 */
	protected static function getUniqueID($prefix = '', $length = 13) {
		return $prefix . Server::get(ISecureRandom::class)->generate(
			$length,
			// Do not use dots and slashes as we use the value for file names
			ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER
		);
	}

	/**
	 * Filter methods
	 *
	 * Returns all methods of the given class,
	 * that are public or abstract and not in the ignoreMethods list,
	 * to be able to fill onlyMethods() with an inverted list.
	 *
	 * @param string $className
	 * @param string[] $filterMethods
	 * @return string[]
	 */
	public function filterClassMethods(string $className, array $filterMethods): array {
		$class = new \ReflectionClass($className);

		$methods = [];
		foreach ($class->getMethods() as $method) {
			if (($method->isPublic() || $method->isAbstract()) && !in_array($method->getName(), $filterMethods, true)) {
				$methods[] = $method->getName();
			}
		}

		return $methods;
	}

	public static function tearDownAfterClass(): void {
		if (!self::$wasDatabaseAllowed && self::$realDatabase !== null) {
			// in case an error is thrown in a test, PHPUnit jumps straight to tearDownAfterClass,
			// so we need the database again
			\OC::$server->registerService(IDBConnection::class, function () {
				return self::$realDatabase;
			});
		}
		$dataDir = Server::get(IConfig::class)->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data-autotest');
		if (self::$wasDatabaseAllowed && Server::get(IDBConnection::class)) {
			$db = Server::get(IDBConnection::class);
			if ($db->inTransaction()) {
				$db->rollBack();
				throw new \Exception('There was a transaction still in progress and needed to be rolled back. Please fix this in your test.');
			}
			$queryBuilder = $db->getQueryBuilder();

			self::tearDownAfterClassCleanShares($queryBuilder);
			self::tearDownAfterClassCleanStorages($queryBuilder);
			self::tearDownAfterClassCleanFileCache($queryBuilder);
		}
		self::tearDownAfterClassCleanStrayDataFiles($dataDir);
		self::tearDownAfterClassCleanStrayHooks();
		self::tearDownAfterClassCleanStrayLocks();

		/** @var SetupManager $setupManager */
		$setupManager = Server::get(SetupManager::class);
		$setupManager->tearDown();

		/** @var MountProviderCollection $mountProviderCollection */
		$mountProviderCollection = Server::get(MountProviderCollection::class);
		$mountProviderCollection->clearProviders();

		/** @var IConfig $config */
		$config = Server::get(IConfig::class);
		$mountProviderCollection->registerProvider(new CacheMountProvider($config));
		$mountProviderCollection->registerHomeProvider(new LocalHomeMountProvider());
		$objectStoreConfig = Server::get(PrimaryObjectStoreConfig::class);
		$mountProviderCollection->registerRootProvider(new RootMountProvider($objectStoreConfig, $config));

		$setupManager->setupRoot();

		parent::tearDownAfterClass();
	}

	/**
	 * Remove all entries from the share table
	 *
	 * @param IQueryBuilder $queryBuilder
	 */
	protected static function tearDownAfterClassCleanShares(IQueryBuilder $queryBuilder) {
		$queryBuilder->delete('share')
			->execute();
	}

	/**
	 * Remove all entries from the storages table
	 *
	 * @param IQueryBuilder $queryBuilder
	 */
	protected static function tearDownAfterClassCleanStorages(IQueryBuilder $queryBuilder) {
		$queryBuilder->delete('storages')
			->execute();
	}

	/**
	 * Remove all entries from the filecache table
	 *
	 * @param IQueryBuilder $queryBuilder
	 */
	protected static function tearDownAfterClassCleanFileCache(IQueryBuilder $queryBuilder) {
		$queryBuilder->delete('filecache')
			->runAcrossAllShards()
			->execute();
	}

	/**
	 * Remove all unused files from the data dir
	 *
	 * @param string $dataDir
	 */
	protected static function tearDownAfterClassCleanStrayDataFiles($dataDir) {
		$knownEntries = [
			'nextcloud.log' => true,
			'audit.log' => true,
			'owncloud.db' => true,
			'.ocdata' => true,
			'..' => true,
			'.' => true,
		];

		if ($dh = opendir($dataDir)) {
			while (($file = readdir($dh)) !== false) {
				if (!isset($knownEntries[$file])) {
					self::tearDownAfterClassCleanStrayDataUnlinkDir($dataDir . '/' . $file);
				}
			}
			closedir($dh);
		}
	}

	/**
	 * Recursive delete files and folders from a given directory
	 *
	 * @param string $dir
	 */
	protected static function tearDownAfterClassCleanStrayDataUnlinkDir($dir) {
		if ($dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (Filesystem::isIgnoredDir($file)) {
					continue;
				}
				$path = $dir . '/' . $file;
				if (is_dir($path)) {
					self::tearDownAfterClassCleanStrayDataUnlinkDir($path);
				} else {
					@unlink($path);
				}
			}
			closedir($dh);
		}
		@rmdir($dir);
	}

	/**
	 * Clean up the list of hooks
	 */
	protected static function tearDownAfterClassCleanStrayHooks() {
		\OC_Hook::clear();
	}

	/**
	 * Clean up the list of locks
	 */
	protected static function tearDownAfterClassCleanStrayLocks() {
		Server::get(ILockingProvider::class)->releaseAll();
	}

	/**
	 * Login and setup FS as a given user,
	 * sets the given user as the current user.
	 *
	 * @param string $user user id or empty for a generic FS
	 */
	protected static function loginAsUser($user = '') {
		self::logout();
		Filesystem::tearDown();
		\OC_User::setUserId($user);
		$userObject = Server::get(IUserManager::class)->get($user);
		if (!is_null($userObject)) {
			$userObject->updateLastLoginTimestamp();
		}
		\OC_Util::setupFS($user);
		if (Server::get(IUserManager::class)->userExists($user)) {
			\OC::$server->getUserFolder($user);
		}
	}

	/**
	 * Logout the current user and tear down the filesystem.
	 */
	protected static function logout() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		// needed for fully logout
		Server::get(IUserSession::class)->setUser(null);
	}

	/**
	 * Run all commands pushed to the bus
	 */
	protected function runCommands() {
		// get the user for which the fs is setup
		$view = Filesystem::getView();
		if ($view) {
			[, $user] = explode('/', $view->getRoot());
		} else {
			$user = null;
		}

		\OC_Util::tearDownFS(); // command can't reply on the fs being setup
		$this->commandBus->run();
		\OC_Util::tearDownFS();

		if ($user) {
			\OC_Util::setupFS($user);
		}
	}

	/**
	 * Check if the given path is locked with a given type
	 *
	 * @param \OC\Files\View $view view
	 * @param string $path path to check
	 * @param int $type lock type
	 * @param bool $onMountPoint true to check the mount point instead of the
	 *                           mounted storage
	 *
	 * @return boolean true if the file is locked with the
	 *                 given type, false otherwise
	 */
	protected function isFileLocked($view, $path, $type, $onMountPoint = false) {
		// Note: this seems convoluted but is necessary because
		// the format of the lock key depends on the storage implementation
		// (in our case mostly md5)

		if ($type === ILockingProvider::LOCK_SHARED) {
			// to check if the file has a shared lock, try acquiring an exclusive lock
			$checkType = ILockingProvider::LOCK_EXCLUSIVE;
		} else {
			// a shared lock cannot be set if exclusive lock is in place
			$checkType = ILockingProvider::LOCK_SHARED;
		}
		try {
			$view->lockFile($path, $checkType, $onMountPoint);
			// no exception, which means the lock of $type is not set
			// clean up
			$view->unlockFile($path, $checkType, $onMountPoint);
			return false;
		} catch (LockedException $e) {
			// we could not acquire the counter-lock, which means
			// the lock of $type was in place
			return true;
		}
	}

	protected function getGroupAnnotations(): array {
		if (method_exists($this, 'getAnnotations')) {
			$annotations = $this->getAnnotations();
			return $annotations['class']['group'] ?? [];
		}

		$r = new \ReflectionClass($this);
		$doc = $r->getDocComment();
		preg_match_all('#@group\s+(.*?)\n#s', $doc, $annotations);
		return $annotations[1] ?? [];
	}

	protected function IsDatabaseAccessAllowed() {
		$annotations = $this->getGroupAnnotations();
		if (isset($annotations)) {
			if (in_array('DB', $annotations) || in_array('SLOWDB', $annotations)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $expectedHtml
	 * @param string $template
	 * @param array $vars
	 */
	protected function assertTemplate($expectedHtml, $template, $vars = []) {
		$requestToken = 12345;
		/** @var Defaults|\PHPUnit\Framework\MockObject\MockObject $l10n */
		$theme = $this->getMockBuilder('\OCP\Defaults')
			->disableOriginalConstructor()->getMock();
		$theme->expects($this->any())
			->method('getName')
			->willReturn('Nextcloud');
		/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject $l10n */
		$l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$t = new Base($template, $requestToken, $l10n, $theme);
		$buf = $t->fetchPage($vars);
		$this->assertHtmlStringEqualsHtmlString($expectedHtml, $buf);
	}

	/**
	 * @param string $expectedHtml
	 * @param string $actualHtml
	 * @param string $message
	 */
	protected function assertHtmlStringEqualsHtmlString($expectedHtml, $actualHtml, $message = '') {
		$expected = new DOMDocument();
		$expected->preserveWhiteSpace = false;
		$expected->formatOutput = true;
		$expected->loadHTML($expectedHtml);

		$actual = new DOMDocument();
		$actual->preserveWhiteSpace = false;
		$actual->formatOutput = true;
		$actual->loadHTML($actualHtml);
		$this->removeWhitespaces($actual);

		$expectedHtml1 = $expected->saveHTML();
		$actualHtml1 = $actual->saveHTML();
		self::assertEquals($expectedHtml1, $actualHtml1, $message);
	}


	private function removeWhitespaces(DOMNode $domNode) {
		foreach ($domNode->childNodes as $node) {
			if ($node->hasChildNodes()) {
				$this->removeWhitespaces($node);
			} else {
				if ($node instanceof \DOMText && $node->isWhitespaceInElementContent()) {
					$domNode->removeChild($node);
				}
			}
		}
	}
}
