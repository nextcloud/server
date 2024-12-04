<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author zulan <git@zulan.net>
 * @author Stephan Orbaugh <stephan.orbaugh@nextcloud.com>
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
namespace OCA\Settings\AppInfo;

use OC\AppFramework\Utility\TimeFactory;
use OC\Authentication\Events\AppPasswordCreatedEvent;
use OC\Authentication\Token\IProvider;
use OC\Server;
use OCA\Settings\Hooks;
use OCA\Settings\Listener\AppPasswordCreatedActivityListener;
use OCA\Settings\Listener\GroupRemovedListener;
use OCA\Settings\Listener\UserAddedToGroupActivityListener;
use OCA\Settings\Listener\UserRemovedFromGroupActivityListener;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCA\Settings\Middleware\SubadminMiddleware;
use OCA\Settings\Search\AppSearch;
use OCA\Settings\Search\SectionSearch;
use OCA\Settings\Search\UserSearch;
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
use OCA\Settings\SetupChecks\DebugMode;
use OCA\Settings\SetupChecks\DefaultPhoneRegionSet;
use OCA\Settings\SetupChecks\EmailTestSuccessful;
use OCA\Settings\SetupChecks\FileLocking;
use OCA\Settings\SetupChecks\ForwardedForHeaders;
use OCA\Settings\SetupChecks\InternetConnectivity;
use OCA\Settings\SetupChecks\JavaScriptModules;
use OCA\Settings\SetupChecks\LegacySSEKeyFormat;
use OCA\Settings\SetupChecks\MaintenanceWindowStart;
use OCA\Settings\SetupChecks\MemcacheConfigured;
use OCA\Settings\SetupChecks\MimeTypeMigrationAvailable;
use OCA\Settings\SetupChecks\MysqlUnicodeSupport;
use OCA\Settings\SetupChecks\OverwriteCliUrl;
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
use OCA\Settings\SetupChecks\SupportedDatabase;
use OCA\Settings\SetupChecks\SystemIs64bit;
use OCA\Settings\SetupChecks\TempSpaceAvailable;
use OCA\Settings\SetupChecks\TransactionIsolation;
use OCA\Settings\UserMigration\AccountMigrator;
use OCA\Settings\WellKnown\ChangePasswordHandler;
use OCA\Settings\WellKnown\SecurityTxtHandler;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\Defaults;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IServerContainer;
use OCP\Settings\IManager;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'settings';

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		// Register Middleware
		$context->registerServiceAlias('SubadminMiddleware', SubadminMiddleware::class);
		$context->registerMiddleware(SubadminMiddleware::class);
		$context->registerSearchProvider(SectionSearch::class);
		$context->registerSearchProvider(AppSearch::class);
		$context->registerSearchProvider(UserSearch::class);

		// Register listeners
		$context->registerEventListener(AppPasswordCreatedEvent::class, AppPasswordCreatedActivityListener::class);
		$context->registerEventListener(UserAddedEvent::class, UserAddedToGroupActivityListener::class);
		$context->registerEventListener(UserRemovedEvent::class, UserRemovedFromGroupActivityListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupRemovedListener::class);

		// Register well-known handlers
		$context->registerWellKnownHandler(SecurityTxtHandler::class);
		$context->registerWellKnownHandler(ChangePasswordHandler::class);

		/**
		 * Core class wrappers
		 */
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$context->registerService('isSubAdmin', function () {
			$userObject = \OC::$server->getUserSession()->getUser();
			$isSubAdmin = false;
			if ($userObject !== null) {
				$isSubAdmin = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($userObject);
			}
			return $isSubAdmin;
		});
		$context->registerService(IProvider::class, function (IAppContainer $appContainer) {
			/** @var IServerContainer $serverContainer */
			$serverContainer = $appContainer->query(IServerContainer::class);
			return $serverContainer->query(IProvider::class);
		});
		$context->registerService(IManager::class, function (IAppContainer $appContainer) {
			/** @var IServerContainer $serverContainer */
			$serverContainer = $appContainer->query(IServerContainer::class);
			return $serverContainer->getSettingsManager();
		});

		$context->registerService(NewUserMailHelper::class, function (IAppContainer $appContainer) {
			/** @var Server $server */
			$server = $appContainer->query(IServerContainer::class);
			/** @var Defaults $defaults */
			$defaults = $server->query(Defaults::class);

			return new NewUserMailHelper(
				$defaults,
				$server->getURLGenerator(),
				$server->getL10NFactory(),
				$server->getMailer(),
				$server->getSecureRandom(),
				new TimeFactory(),
				$server->getConfig(),
				$server->getCrypto(),
				Util::getDefaultEmailAddress('no-reply')
			);
		});
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
		$context->registerSetupCheck(DebugMode::class);
		$context->registerSetupCheck(DefaultPhoneRegionSet::class);
		$context->registerSetupCheck(EmailTestSuccessful::class);
		$context->registerSetupCheck(FileLocking::class);
		$context->registerSetupCheck(ForwardedForHeaders::class);
		$context->registerSetupCheck(InternetConnectivity::class);
		$context->registerSetupCheck(JavaScriptModules::class);
		$context->registerSetupCheck(LegacySSEKeyFormat::class);
		$context->registerSetupCheck(MaintenanceWindowStart::class);
		$context->registerSetupCheck(MemcacheConfigured::class);
		$context->registerSetupCheck(MimeTypeMigrationAvailable::class);
		$context->registerSetupCheck(MysqlUnicodeSupport::class);
		$context->registerSetupCheck(OverwriteCliUrl::class);
		$context->registerSetupCheck(PhpDefaultCharset::class);
		$context->registerSetupCheck(PhpDisabledFunctions::class);
		$context->registerSetupCheck(PhpFreetypeSupport::class);
		$context->registerSetupCheck(PhpGetEnv::class);
		// Temporarily disabled $context->registerSetupCheck(PhpMaxFileSize::class);
		$context->registerSetupCheck(PhpMemoryLimit::class);
		$context->registerSetupCheck(PhpModules::class);
		$context->registerSetupCheck(PhpOpcacheSetup::class);
		$context->registerSetupCheck(PhpOutdated::class);
		$context->registerSetupCheck(PhpOutputBuffering::class);
		$context->registerSetupCheck(RandomnessSecure::class);
		$context->registerSetupCheck(ReadOnlyConfig::class);
		$context->registerSetupCheck(SchedulingTableSize::class);
		$context->registerSetupCheck(SupportedDatabase::class);
		$context->registerSetupCheck(SystemIs64bit::class);
		$context->registerSetupCheck(TempSpaceAvailable::class);
		$context->registerSetupCheck(TransactionIsolation::class);
		$context->registerSetupCheck(PushService::class);

		$context->registerUserMigrator(AccountMigrator::class);
	}

	public function boot(IBootContext $context): void {
		Util::connectHook('OC_User', 'post_setPassword', $this, 'onChangePassword');
		Util::connectHook('OC_User', 'changeUser', $this, 'onChangeInfo');
	}

	/**
	 * @param array $parameters
	 * @throws \InvalidArgumentException
	 * @throws \BadMethodCallException
	 * @throws \Exception
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function onChangePassword(array $parameters) {
		/** @var Hooks $hooks */
		$hooks = $this->getContainer()->query(Hooks::class);
		$hooks->onChangePassword($parameters['uid']);
	}

	/**
	 * @param array $parameters
	 * @throws \InvalidArgumentException
	 * @throws \BadMethodCallException
	 * @throws \Exception
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function onChangeInfo(array $parameters) {
		if ($parameters['feature'] !== 'eMailAddress') {
			return;
		}

		/** @var Hooks $hooks */
		$hooks = $this->getContainer()->query(Hooks::class);
		$hooks->onChangeEmail($parameters['user'], $parameters['old_value']);
	}
}
