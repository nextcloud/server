<?php

namespace OC\AppFramework\DependencyInjection;

use OC\AppFramework\Utility\SimpleContainer;
use OC;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IAppData;
use OCP\Files\Mount\IMountManager;
use OCP\IAppConfig;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\IOutput;
use OCP\IAvatarManager;
use OCP\IGroupManager;
use OCP\Files\IMimeTypeDetector;
use OCP\RichObjectStrings\IValidator;
use OCP\Util;

class BasicContainer extends SimpleContainer {

	public function __construct() {
		parent::__construct();

		$this->registerService(IAppConfig::class, function() {
			return $this->getServer()->getAppConfig();
		});
		$this->registerService(IAppManager::class, function() {
			return $this->getServer()->getAppManager();
		});

		$this->registerService(IOutput::class, function(){
			return new OC\AppFramework\Http\Output($this->getServer()->getWebRoot());
		});

		$this->registerService(\OCP\Authentication\LoginCredentials\IStore::class, function() {
			return $this->getServer()->query(\OCP\Authentication\LoginCredentials\IStore::class);
		});

		$this->registerService(IAvatarManager::class, function() {
			return $this->getServer()->getAvatarManager();
		});

		$this->registerService('OCP\\Activity\\IManager', function() {
			return $this->getServer()->getActivityManager();
		});
		$this->registerService(\OCP\Activity\IEventMerger::class, function() {
			return $this->getServer()->query(\OCP\Activity\IEventMerger::class);
		});

		$this->registerService('OCP\\ICache', function() {
			return $this->getServer()->getCache();
		});

		$this->registerService('OCP\\ICacheFactory', function() {
			return $this->getServer()->getMemCacheFactory();
		});

		$this->registerService('OC\\CapabilitiesManager', function() {
			return $this->getServer()->getCapabilitiesManager();
		});

		$this->registerService('OCP\Comments\ICommentsManager', function() {
			return $this->getServer()->getCommentsManager();
		});

		$this->registerService('OCP\\IConfig', function() {
			return $this->getServer()->getConfig();
		});

		$this->registerService('OCP\\Contacts\\IManager', function() {
			return $this->getServer()->getContactsManager();
		});

		$this->registerService('OCP\\IDateTimeZone', function() {
			return $this->getServer()->getDateTimeZone();
		});

		$this->registerService('OCP\\IDateTimeFormatter', function() {
			return $this->getServer()->getDateTimeFormatter();
		});

		$this->registerService('OCP\\IDBConnection', function() {
			return $this->getServer()->getDatabaseConnection();
		});

		$this->registerService('OCP\\Diagnostics\\IEventLogger', function() {
			return $this->getServer()->getEventLogger();
		});

		$this->registerService('OCP\\Diagnostics\\IQueryLogger', function() {
			return $this->getServer()->getQueryLogger();
		});

		$this->registerService(ICloudIdManager::class, function() {
			return $this->getServer()->getCloudIdManager();
		});

		$this->registerService(IMimeTypeDetector::class, function() {
			return $this->getServer()->getMimeTypeDetector();
		});

		$this->registerService('OCP\\Files\\Config\\IMountProviderCollection', function() {
			return $this->getServer()->getMountProviderCollection();
		});

		$this->registerService('OCP\\Files\\Config\\IUserMountCache', function() {
			return $this->getServer()->getUserMountCache();
		});

		$this->registerService('OCP\\Files\\IRootFolder', function() {
			return $this->getServer()->getRootFolder();
		});

		$this->registerService('OCP\\Files\\Folder', function() {
			return $this->getServer()->getUserFolder();
		});

		$this->registerService('OCP\\Http\\Client\\IClientService', function() {
			return $this->getServer()->getHTTPClientService();
		});

		$this->registerService(IAppData::class, function (SimpleContainer $c) {
			return $this->getServer()->getAppDataDir($c->query('AppName'));
		});

		$this->registerService(IGroupManager::class, function() {
			return $this->getServer()->getGroupManager();
		});


		$this->registerService('OCP\\Http\\Client\\IClientService', function() {
			return $this->getServer()->getHTTPClientService();
		});

		$this->registerService('OCP\\IL10N', function($c) {
			return $this->getServer()->getL10N($c->query('AppName'));
		});

		$this->registerService('OCP\\L10N\\IFactory', function($c) {
			return $this->getServer()->getL10NFactory();
		});

		$this->registerService('OCP\\ILogger', function($c) {
			return $this->getServer()->getLogger();
		});

		$this->registerService('OCP\\BackgroundJob\\IJobList', function($c) {
			return $this->getServer()->getJobList();
		});

		$this->registerAlias('OCP\\AppFramework\\Utility\\IControllerMethodReflector', 'OC\AppFramework\Utility\ControllerMethodReflector');
		$this->registerAlias('ControllerMethodReflector', 'OCP\\AppFramework\\Utility\\IControllerMethodReflector');

		$this->registerService('OCP\\Files\\IMimeTypeDetector', function($c) {
			return $this->getServer()->getMimeTypeDetector();
		});

		$this->registerService('OCP\\Mail\\IMailer', function() {
			return $this->getServer()->getMailer();
		});

		$this->registerService('OCP\\INavigationManager', function($c) {
			return $this->getServer()->getNavigationManager();
		});

		$this->registerService('OCP\\Notification\IManager', function($c) {
			return $this->getServer()->getNotificationManager();
		});

		$this->registerService('OCP\\IPreview', function($c) {
			return $this->getServer()->getPreviewManager();
		});

		$this->registerService('OCP\\IRequest', function () {
			return $this->getServer()->getRequest();
		});
		$this->registerAlias('Request', 'OCP\\IRequest');

		$this->registerService('OCP\\ITagManager', function($c) {
			return $this->getServer()->getTagManager();
		});

		$this->registerService('OCP\\ITempManager', function($c) {
			return $this->getServer()->getTempManager();
		});

		$this->registerAlias('OCP\\AppFramework\\Utility\\ITimeFactory', 'OC\AppFramework\Utility\TimeFactory');
		$this->registerAlias('TimeFactory', 'OCP\\AppFramework\\Utility\\ITimeFactory');


		$this->registerService('OCP\\Route\\IRouter', function($c) {
			return $this->getServer()->getRouter();
		});

		$this->registerService('OCP\\ISearch', function($c) {
			return $this->getServer()->getSearch();
		});

		$this->registerService('OCP\\ISearch', function($c) {
			return $this->getServer()->getSearch();
		});

		$this->registerService('OCP\\Security\\ICrypto', function($c) {
			return $this->getServer()->getCrypto();
		});

		$this->registerService('OCP\\Security\\IHasher', function($c) {
			return $this->getServer()->getHasher();
		});

		$this->registerService('OCP\\Security\\ICredentialsManager', function($c) {
			return $this->getServer()->getCredentialsManager();
		});

		$this->registerService('OCP\\Security\\ISecureRandom', function($c) {
			return $this->getServer()->getSecureRandom();
		});

		$this->registerService('OCP\\Share\\IManager', function($c) {
			return $this->getServer()->getShareManager();
		});

		$this->registerService('OCP\\SystemTag\\ISystemTagManager', function() {
			return $this->getServer()->getSystemTagManager();
		});

		$this->registerService('OCP\\SystemTag\\ISystemTagObjectMapper', function() {
			return $this->getServer()->getSystemTagObjectMapper();
		});

		$this->registerService('OCP\\IURLGenerator', function($c) {
			return $this->getServer()->getURLGenerator();
		});

		$this->registerService('OCP\\IUserManager', function($c) {
			return $this->getServer()->getUserManager();
		});

		$this->registerService('OCP\\IUserSession', function($c) {
			return $this->getServer()->getUserSession();
		});
		$this->registerAlias(\OC\User\Session::class, \OCP\IUserSession::class);

		$this->registerService('OCP\\ISession', function($c) {
			return $this->getServer()->getSession();
		});

		$this->registerService('OCP\\Security\\IContentSecurityPolicyManager', function($c) {
			return $this->getServer()->getContentSecurityPolicyManager();
		});

		$this->registerService('ServerContainer', function ($c) {
			return $this->getServer();
		});
		$this->registerAlias('OCP\\IServerContainer', 'ServerContainer');

		$this->registerService('Symfony\Component\EventDispatcher\EventDispatcherInterface', function ($c) {
			return $this->getServer()->getEventDispatcher();
		});

		$this->registerService('OCP\WorkflowEngine\IManager', function ($c) {
			return $c->query('OCA\WorkflowEngine\Manager');
		});

		$this->registerService('OCP\\AppFramework\\IAppContainer', function ($c) {
			return $c;
		});
		$this->registerService(IMountManager::class, function () {
			return $this->getServer()->getMountManager();
		});

		// commonly used attributes
		$this->registerService('UserId', function ($c) {
			return $c->query('OCP\\IUserSession')->getSession()->get('user_id');
		});

		$this->registerService('WebRoot', function ($c) {
			return $c->query('ServerContainer')->getWebRoot();
		});

		$this->registerService('fromMailAddress', function() {
			return Util::getDefaultEmailAddress('no-reply');
		});

		$this->registerService('OC_Defaults', function ($c) {
			return $c->getServer()->getThemingDefaults();
		});

		$this->registerService('OCP\Encryption\IManager', function ($c) {
			return $this->getServer()->getEncryptionManager();
		});

		$this->registerService(IValidator::class, function($c) {
			return $c->query(OC\RichObjectStrings\Validator::class);
		});

		$this->registerService(\OC\Security\IdentityProof\Manager::class, function ($c) {
			return new \OC\Security\IdentityProof\Manager(
				$this->getServer()->getAppDataDir('identityproof'),
				$this->getServer()->getCrypto()
			);
		});

	}

	private function getServer() {
		return \OC::$server;
	}
}
