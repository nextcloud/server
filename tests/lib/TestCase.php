<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\App\AppStore\Fetcher\AppFetcher;
use OC\Command\QueueBus;
use OC\Files\AppData\Factory;
use OC\Files\Cache\Storage;
use OC\Files\Config\MountProviderCollection;
use OC\Files\Filesystem;
use OC\Files\Mount\CacheMountProvider;
use OC\Files\Mount\LocalHomeMountProvider;
use OC\Files\Mount\RootMountProvider;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\SetupManager;
use OC\Files\View;
use OC\Installer;
use OC\Updater;
use OCP\Command\IBus;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Security\ISecureRandom;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Psr\Container\ContainerExceptionInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase {
	private QueueBus $commandBus;

	protected static ?IDBConnection $realDatabase = null;
	private static bool $wasDatabaseAllowed = false;
	protected array $services = [];

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

	public function overwriteService(string $name, mixed $newService): bool {
		if (isset($this->services[$name])) {
			return false;
		}

		try {
			$this->services[$name] = Server::get($name);
		} catch (ContainerExceptionInterface $e) {
			$this->services[$name] = false;
		}
		/** @psalm-suppress InternalMethod */
		$container = \OC::$server->getAppContainerForService($name);
		$container = $container ?? \OC::$server;

		$container->registerService($name, function () use ($newService) {
			return $newService;
		});

		return true;
	}

	public function restoreService(string $name): bool {
		if (isset($this->services[$name])) {
			$oldService = $this->services[$name];

			/** @psalm-suppress InternalMethod */
			$container = \OC::$server->getAppContainerForService($name);
			$container = $container ?? \OC::$server;

			if ($oldService !== false) {
				$container->registerService($name, function () use ($oldService) {
					return $oldService;
				});
			} else {
				unset($container[$oldService]);
			}


			unset($this->services[$name]);
			return true;
		}

		return false;
	}

	public function restoreAllServices(): void {
		foreach ($this->services as $name => $service) {
			$this->restoreService($name);
		}
	}

	protected function getTestTraits(): array {
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
			/** @psalm-suppress InternalMethod */
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
			/** @psalm-suppress InternalMethod */
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
			return $method->invokeArgs($object, $parameters);
		} elseif ($reflection->hasProperty($methodName)) {
			$property = $reflection->getProperty($methodName);

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

		// Ensure we start with fresh instances of some classes to reduce side-effects between tests
		/** @psalm-suppress DeprecatedMethod */
		unset(\OC::$server[Factory::class]);
		/** @psalm-suppress DeprecatedMethod */
		unset(\OC::$server[AppFetcher::class]);
		/** @psalm-suppress DeprecatedMethod */
		unset(\OC::$server[Installer::class]);
		/** @psalm-suppress DeprecatedMethod */
		unset(\OC::$server[Updater::class]);

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
	 */
	protected static function tearDownAfterClassCleanShares(IQueryBuilder $queryBuilder): void {
		$queryBuilder->delete('share')
			->executeStatement();
	}

	/**
	 * Remove all entries from the storages table
	 */
	protected static function tearDownAfterClassCleanStorages(IQueryBuilder $queryBuilder): void {
		$queryBuilder->delete('storages')
			->executeStatement();
	}

	/**
	 * Remove all entries from the filecache table
	 */
	protected static function tearDownAfterClassCleanFileCache(IQueryBuilder $queryBuilder): void {
		$queryBuilder->delete('filecache')
			->runAcrossAllShards()
			->executeStatement();
	}

	/**
	 * Remove all unused files from the data dir
	 *
	 * @param string $dataDir
	 */
	protected static function tearDownAfterClassCleanStrayDataFiles(string $dataDir): void {
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
	protected static function tearDownAfterClassCleanStrayDataUnlinkDir(string $dir): void {
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
	protected static function tearDownAfterClassCleanStrayHooks(): void {
		\OC_Hook::clear();
	}

	/**
	 * Clean up the list of locks
	 */
	protected static function tearDownAfterClassCleanStrayLocks(): void {
		Server::get(ILockingProvider::class)->releaseAll();
	}

	/**
	 * Login and setup FS as a given user,
	 * sets the given user as the current user.
	 *
	 * @param string $user user id or empty for a generic FS
	 */
	protected static function loginAsUser(string $user = ''): void {
		self::logout();
		Filesystem::tearDown();
		\OC_User::setUserId($user);
		$userManager = Server::get(IUserManager::class);
		$setupManager = Server::get(SetupManager::class);
		$userObject = $userManager->get($user);
		if (!is_null($userObject)) {
			$userObject->updateLastLoginTimestamp();
			$setupManager->setupForUser($userObject);
			$rootFolder = Server::get(IRootFolder::class);
			$rootFolder->getUserFolder($user);
		}
	}

	/**
	 * Logout the current user and tear down the filesystem.
	 */
	protected static function logout(): void {
		Server::get(SetupManager::class)->tearDown();
		$userSession = Server::get(\OC\User\Session::class);
		$userSession->getSession()->set('user_id', '');
		// needed for fully logout
		$userSession->setUser(null);
	}

	/**
	 * Run all commands pushed to the bus
	 */
	protected function runCommands(): void {
		$setupManager = Server::get(SetupManager::class);
		$session = Server::get(IUserSession::class);
		$user = $session->getUser();

		$setupManager->tearDown(); // commands can't reply on the fs being setup
		$this->commandBus->run();
		$setupManager->tearDown();

		if ($user) {
			$setupManager->setupForUser($user);
		}
	}

	/**
	 * Check if the given path is locked with a given type
	 *
	 * @param View $view view
	 * @param string $path path to check
	 * @param int $type lock type
	 * @param bool $onMountPoint true to check the mount point instead of the
	 *                           mounted storage
	 *
	 * @return boolean true if the file is locked with the
	 *                 given type, false otherwise
	 */
	protected function isFileLocked(View $view, string $path, int $type, bool $onMountPoint = false) {
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

	/**
	 * @return list<string>
	 */
	protected function getGroupAnnotations(): array {
		if (method_exists($this, 'getAnnotations')) {
			$annotations = $this->getAnnotations();
			return $annotations['class']['group'] ?? [];
		}

		$r = new \ReflectionClass($this);
		$doc = $r->getDocComment();

		if (class_exists(Group::class)) {
			$attributes = array_map(function (\ReflectionAttribute $attribute): string {
				/** @var Group $group */
				$group = $attribute->newInstance();
				return $group->name();
			}, $r->getAttributes(Group::class));
			if (count($attributes) > 0) {
				return $attributes;
			}
		}
		preg_match_all('#@group\s+(.*?)\n#s', $doc, $annotations);
		return $annotations[1] ?? [];
	}

	protected function IsDatabaseAccessAllowed(): bool {
		$annotations = $this->getGroupAnnotations();
		return in_array('DB', $annotations) || in_array('SLOWDB', $annotations);
	}
}
