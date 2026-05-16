<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\AppInfo;

use OC\AppFramework\Utility\TimeFactory;
use OC\Authentication\Events\AppPasswordCreatedEvent;
use OC\Authentication\Token\IProvider;
use OC\Settings\Manager;
use OCA\Settings\ConfigLexicon;
use OCA\Settings\Hooks;
use OCA\Settings\Listener\AppPasswordCreatedActivityListener;
use OCA\Settings\Listener\GroupRemovedListener;
use OCA\Settings\Listener\MailProviderListener;
use OCA\Settings\Listener\UserAddedToGroupActivityListener;
use OCA\Settings\Listener\UserRemovedFromGroupActivityListener;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCA\Settings\Middleware\SubadminMiddleware;
use OCA\Settings\Search\SectionSearch;
use OCA\Settings\Search\UserSearch;
use OCA\Settings\Settings\Admin\MailProvider;
use OCA\Settings\SetupChecks\AllowedAdminRanges;
use OCA\Settings\SetupChecks\AppDirsWithDifferentOwner;
use OCA\Settings\SetupChecks\BruteForceThrottler;
use OCA\Settings\SetupChecks\CheckUserCertificates;
use OCA\Settings\SetupChecks\CodeIntegrity;
use OCA\Settings\SetupChecks\CronErrors;
use OCA\Settings\SetupChecks\CronInfo;
use OCA\Settings\SetupChecks\DatabaseHasMissingColumns;
use OCA\Settings\SetupChecks\DatabaseHasMissingIndices;
use OCA\Settings\SetupChecks\DatabaseHasMissingPrimaryKeys;
use OCA\Settings\SetupChecks\DatabasePendingBigIntConversions;
use OCA\Settings\SetupChecks\DataDirectoryProtected;
use OCA\Settings\SetupChecks\DebugMode;
use OCA\Settings\SetupChecks\DefaultPhoneRegionSet;
use OCA\Settings\SetupChecks\EmailTestSuccessful;
use OCA\Settings\SetupChecks\FileLocking;
use OCA\Settings\SetupChecks\ForwardedForHeaders;
use OCA\Settings\SetupChecks\HttpsUrlGeneration;
use OCA\Settings\SetupChecks\InternetConnectivity;
use OCA\Settings\SetupChecks\JavaScriptModules;
use OCA\Settings\SetupChecks\JavaScriptSourceMaps;
use OCA\Settings\SetupChecks\LegacySSEKeyFormat;
use OCA\Settings\SetupChecks\MaintenanceWindowStart;
use OCA\Settings\SetupChecks\MemcacheConfigured;
use OCA\Settings\SetupChecks\MimeTypeMigrationAvailable;
use OCA\Settings\SetupChecks\MysqlRowFormat;
use OCA\Settings\SetupChecks\MysqlUnicodeSupport;
use OCA\Settings\SetupChecks\OcxProviders;
use OCA\Settings\SetupChecks\OverwriteCliUrl;
use OCA\Settings\SetupChecks\PhpApcuConfig;
use OCA\Settings\SetupChecks\PhpDefaultCharset;
use OCA\Settings\SetupChecks\PhpDisabledFunctions;
use OCA\Settings\SetupChecks\PhpFreetypeSupport;
use OCA\Settings\SetupChecks\PhpGetEnv;
use OCA\Settings\SetupChecks\PhpMemoryLimit;
use OCA\Settings\SetupChecks\PhpModules;
use OCA\Settings\SetupChecks\PhpOpcacheSetup;
use OCA\Settings\SetupChecks\PhpOutdated;
use OCA\Settings\SetupChecks\PhpOutputBuffering;
use OCA\Settings\SetupChecks\PushService;
use OCA\Settings\SetupChecks\RandomnessSecure;
use OCA\Settings\SetupChecks\ReadOnlyConfig;
use OCA\Settings\SetupChecks\SchedulingTableSize;
use OCA\Settings\SetupChecks\SecurityHeaders;
use OCA\Settings\SetupChecks\ServerIdConfig;
use OCA\Settings\SetupChecks\SupportedDatabase;
use OCA\Settings\SetupChecks\SystemIs64bit;
use OCA\Settings\SetupChecks\TaskProcessingPickupSpeed;
use OCA\Settings\SetupChecks\TaskProcessingSuccessRate;
use OCA\Settings\SetupChecks\TaskProcessingWorkerIsRunning;
use OCA\Settings\SetupChecks\TempSpaceAvailable;
use OCA\Settings\SetupChecks\TransactionIsolation;
use OCA\Settings\SetupChecks\TwoFactorConfiguration;
use OCA\Settings\SetupChecks\WellKnownUrls;
use OCA\Settings\SetupChecks\Woff2Loading;
use OCA\Settings\UserMigration\AccountMigrator;
use OCA\Settings\WellKnown\ChangePasswordHandler;
use OCA\Settings\WellKnown\SecurityTxtHandler;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Defaults;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;
use OCP\Settings\IManager;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'settings';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		// Register Middleware
		$context->registerServiceAlias('SubadminMiddleware', SubadminMiddleware::class);
		$context->registerMiddleware(SubadminMiddleware::class);
		$context->registerSearchProvider(SectionSearch::class);
		$context->registerSearchProvider(UserSearch::class);

		$context->registerConfigLexicon(ConfigLexicon::class);

		// Register listeners
		$context->registerEventListener(AppPasswordCreatedEvent::class, AppPasswordCreatedActivityListener::class);
		$context->registerEventListener(UserAddedEvent::class, UserAddedToGroupActivityListener::class);
		$context->registerEventListener(UserRemovedEvent::class, UserRemovedFromGroupActivityListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupRemovedListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, Hooks::class);
		$context->registerEventListener(UserChangedEvent::class, Hooks::class);

		// Register Mail Provider listeners
		$context->registerEventListener(DeclarativeSettingsGetValueEvent::class, MailProviderListener::class);
		$context->registerEventListener(DeclarativeSettingsSetValueEvent::class, MailProviderListener::class);

		// Register well-known handlers
		$context->registerWellKnownHandler(SecurityTxtHandler::class);
		$context->registerWellKnownHandler(ChangePasswordHandler::class);

		// Register Settings Form(s)
		$context->registerDeclarativeSettings(MailProvider::class);

		/**
		 * Core class wrappers
		 */
		$context->registerService(IProvider::class, function (): IProvider {
			return Server::get(IProvider::class);
		});
		$context->registerService(IManager::class, function (): Manager {
			return  Server::get(Manager::class);
		});

		$context->registerService(NewUserMailHelper::class, function (ContainerInterface $appContainer) {
			return new NewUserMailHelper(
				Server::get(Defaults::class),
				$appContainer->get(IURLGenerator::class),
				$appContainer->get(IFactory::class),
				$appContainer->get(IMailer::class),
				$appContainer->get(ISecureRandom::class),
				new TimeFactory(),
				$appContainer->get(IConfig::class),
				$appContainer->get(ICrypto::class),
				Util::getDefaultEmailAddress('no-reply')
			);
		});
		$context->registerSetupCheck(AllowedAdminRanges::class);
		$context->registerSetupCheck(AppDirsWithDifferentOwner::class);
		$context->registerSetupCheck(BruteForceThrottler::class);
		$context->registerSetupCheck(CheckUserCertificates::class);
		$context->registerSetupCheck(CodeIntegrity::class);
		$context->registerSetupCheck(CronErrors::class);
		$context->registerSetupCheck(CronInfo::class);
		$context->registerSetupCheck(DatabaseHasMissingColumns::class);
		$context->registerSetupCheck(DatabaseHasMissingIndices::class);
		$context->registerSetupCheck(DatabaseHasMissingPrimaryKeys::class);
		$context->registerSetupCheck(DatabasePendingBigIntConversions::class);
		$context->registerSetupCheck(DataDirectoryProtected::class);
		$context->registerSetupCheck(DebugMode::class);
		$context->registerSetupCheck(DefaultPhoneRegionSet::class);
		$context->registerSetupCheck(EmailTestSuccessful::class);
		$context->registerSetupCheck(FileLocking::class);
		$context->registerSetupCheck(ForwardedForHeaders::class);
		$context->registerSetupCheck(HttpsUrlGeneration::class);
		$context->registerSetupCheck(InternetConnectivity::class);
		$context->registerSetupCheck(JavaScriptSourceMaps::class);
		$context->registerSetupCheck(JavaScriptModules::class);
		$context->registerSetupCheck(LegacySSEKeyFormat::class);
		$context->registerSetupCheck(MaintenanceWindowStart::class);
		$context->registerSetupCheck(MemcacheConfigured::class);
		$context->registerSetupCheck(MimeTypeMigrationAvailable::class);
		$context->registerSetupCheck(MysqlRowFormat::class);
		$context->registerSetupCheck(MysqlUnicodeSupport::class);
		$context->registerSetupCheck(OcxProviders::class);
		$context->registerSetupCheck(OverwriteCliUrl::class);
		$context->registerSetupCheck(PhpDefaultCharset::class);
		$context->registerSetupCheck(PhpDisabledFunctions::class);
		$context->registerSetupCheck(PhpFreetypeSupport::class);
		$context->registerSetupCheck(PhpApcuConfig::class);
		$context->registerSetupCheck(PhpGetEnv::class);
		// Temporarily disabled $context->registerSetupCheck(PhpMaxFileSize::class);
		$context->registerSetupCheck(PhpMemoryLimit::class);
		$context->registerSetupCheck(PhpModules::class);
		$context->registerSetupCheck(PhpOpcacheSetup::class);
		$context->registerSetupCheck(PhpOutdated::class);
		$context->registerSetupCheck(PhpOutputBuffering::class);
		$context->registerSetupCheck(RandomnessSecure::class);
		$context->registerSetupCheck(ReadOnlyConfig::class);
		$context->registerSetupCheck(SecurityHeaders::class);
		$context->registerSetupCheck(ServerIdConfig::class);
		$context->registerSetupCheck(SchedulingTableSize::class);
		$context->registerSetupCheck(SupportedDatabase::class);
		$context->registerSetupCheck(SystemIs64bit::class);
		$context->registerSetupCheck(TaskProcessingPickupSpeed::class);
		$context->registerSetupCheck(TaskProcessingSuccessRate::class);
		$context->registerSetupCheck(TaskProcessingWorkerIsRunning::class);
		$context->registerSetupCheck(TempSpaceAvailable::class);
		$context->registerSetupCheck(TransactionIsolation::class);
		$context->registerSetupCheck(TwoFactorConfiguration::class);
		$context->registerSetupCheck(PushService::class);
		$context->registerSetupCheck(WellKnownUrls::class);
		$context->registerSetupCheck(Woff2Loading::class);

		$context->registerUserMigrator(AccountMigrator::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		$context->injectFn($this->registerNavigationEntries(...));
	}

	/**
	 * Registers the navigation entries for the user settings.
	 * Needed as some entries are dynamic and thus we cannot use the appinfo/info.xml
	 *
	 * Registers the following entries:
	 * - Appearance and accessibility
	 * - Personal settings (named "Settings" for non-admins)
	 * - Accounts (only for subadmins)
	 * - Help & privacy (conditionally enabled based on config)
	 */
	public function registerNavigationEntries(
		INavigationManager $navigationManager,
		IURLGenerator $urlGenerator,
		IUserSession $userSession,
		IConfig $config,
	): void {
		if ($userSession->getUser() === null) {
			return;
		}

		$l = Server::get(IFactory::class)
			->get('settings');
		$groupManager = Server::get(IGroupManager::class);
		$isAdmin = $groupManager->isAdmin($userSession->getUser()->getUID());

		// Accessibility settings - the URL is dynamic (route parameters) which is currently not supported by appinfo.xml
		$navigationManager->add([
			'type' => 'settings',
			'id' => 'accessibility_settings',
			'order' => 2,
			'href' => $urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'theming']),
			'name' => $l->t('Appearance and accessibility'),
			'icon' => $urlGenerator->imagePath('theming', 'accessibility-dark.svg'),
		]);

		// Personal settings - this entry is dynamic so we cannot use appinfo
		$navigationManager->add([
			'type' => 'settings',
			'id' => 'settings_personal',
			'order' => 3,
			'href' => $urlGenerator->linkToRoute('settings.PersonalSettings.index'),
			'name' => $isAdmin
				? $l->t('Personal settings')
				: $l->t('Settings'),
			'icon' => $isAdmin
				? $urlGenerator->imagePath('settings', 'personal.svg')
				: $urlGenerator->imagePath('settings', 'admin.svg'),
		]);

		// User management is conditionally enabled for subadmins, but appinfo currently only supports full admins
		/** @var \OC\Group\Manager $groupManager */
		$isSubAdmin = $groupManager->getSubAdmin()->isSubAdmin($userSession->getUser());
		if ($isSubAdmin) {
			$navigationManager->add([
				'type' => 'settings',
				'id' => 'core_users',
				'order' => 6,
				'href' => $urlGenerator->linkToRoute('settings.Users.usersList'),
				'name' => $l->t('Accounts'),
				'icon' => $urlGenerator->imagePath('settings', 'users.svg'),
			]);
		}

		// conditionally enabled navigation entry
		if ($config->getSystemValueBool('knowledgebaseenabled', true)) {
			$navigationManager->add([
				'type' => 'settings',
				'id' => 'help',
				'order' => 99998,
				'href' => $urlGenerator->linkToRoute('settings.Help.help'),
				'name' => $l->t('Help & privacy'),
				'icon' => $urlGenerator->imagePath('settings', 'help.svg'),
			]);
		}
	}
}
