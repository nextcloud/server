<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use OCP\App\IAppManager;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IInitialStateService;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Util;
use Throwable;

class UpgradePageController {
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly IDBConnection $connection,
		private readonly IUserManager $userManager,
		private readonly IInitialStateService $initialState,
		private readonly IUrlGenerator $urlGenerator,
		private readonly ServerVersion $serverVersion,
		private readonly IAppConfig $appConfig,
	) {

	}

	/**
	 * Prints the upgrade page
	 */
	public function printUpgradePage(\OC\SystemConfig $systemConfig): Response {
		$cliUpgradeLink = $systemConfig->getValue('upgrade.cli-upgrade-link', '');
		$disableWebUpdater = $systemConfig->getValue('upgrade.disable-web', false);
		$tooBig = false;
		if (!$disableWebUpdater) {
			if ($this->appManager->isEnabledForAnyone('user_ldap')) {
				$qb = $this->connection->getQueryBuilder();

				$result = $qb->select($qb->func()->count('*', 'user_count'))
					->from('ldap_user_mapping')
					->executeQuery();
				$row = $result->fetch();
				$result->closeCursor();

				$tooBig = ($row['user_count'] > 50);
			}
			if (!$tooBig && $this->appManager->isEnabledForAnyone('user_saml')) {
				$qb = $this->connection->getQueryBuilder();

				$result = $qb->select($qb->func()->count('*', 'user_count'))
					->from('user_saml_users')
					->executeQuery();
				$row = $result->fetch();
				$result->closeCursor();

				$tooBig = ($row['user_count'] > 50);
			}
			if (!$tooBig) {
				// count users
				$totalUsers = $this->userManager->countUsersTotal(51);
				$tooBig = ($totalUsers > 50);
			}
		}
		$ignoreTooBigWarning = isset($_GET['IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup'])
			&& $_GET['IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup'] === 'IAmSuperSureToDoThis';

		Util::addTranslations('core');
		Util::addScript('core', 'common');
		Util::addScript('core', 'main');
		Util::addScript('core', 'update');

		$serverVersion = \OCP\Server::get(\OCP\ServerVersion::class);
		if ($disableWebUpdater || ($tooBig && !$ignoreTooBigWarning)) {
			// send http status 503
			http_response_code(503);
			header('Retry-After: 120');

			$this->initialState->provideInitialState('core', 'updaterView', 'adminCli');
			$this->initialState->provideInitialState('core', 'updateInfo', [
				'cliUpgradeLink' => $cliUpgradeLink ?: $this->urlGenerator->linkToDocs('admin-cli-upgrade'),
				'productName' => self::getProductName(),
				'version' => $serverVersion->getVersionString(),
				'tooBig' => $tooBig,
			]);

			// render error page
			return new TemplateResponse('', 'update', [], TemplateResponse::RENDER_AS_GUEST);
		}

		// check whether this is a core update or apps update
		$installedVersion = $systemConfig->getValue('version', '0.0.0');
		$currentVersion = implode('.', $serverVersion->getVersion());

		// if not a core upgrade, then it's apps upgrade
		$isAppsOnlyUpgrade = version_compare($currentVersion, $installedVersion, '=');

		$oldTheme = $systemConfig->getValue('theme');
		$systemConfig->setValue('theme', '');

		/** @var \OC\App\AppManager $appManager */
		$appManager = Server::get(\OCP\App\IAppManager::class);

		// get third party apps
		$ocVersion = $serverVersion->getVersion();
		$ocVersion = implode('.', $ocVersion);
		$incompatibleApps = $appManager->getIncompatibleApps($ocVersion);
		$incompatibleOverwrites = $systemConfig->getValue('app_install_overwrite', []);
		$incompatibleShippedApps = [];
		$incompatibleDisabledApps = [];
		foreach ($incompatibleApps as $appInfo) {
			if ($appManager->isShipped($appInfo['id'])) {
				$incompatibleShippedApps[] = $appInfo['name'] . ' (' . $appInfo['id'] . ')';
			}
			if (!in_array($appInfo['id'], $incompatibleOverwrites)) {
				$incompatibleDisabledApps[] = $appInfo;
			}
		}

		if (!empty($incompatibleShippedApps)) {
			$l = Server::get(\OCP\L10N\IFactory::class)->get('core');
			$hint = $l->t('Application %1$s is not present or has a non-compatible version with this server. Please check the apps directory.', [implode(', ', $incompatibleShippedApps)]);
			throw new \OCP\HintException('Application ' . implode(', ', $incompatibleShippedApps) . ' is not present or has a non-compatible version with this server. Please check the apps directory.', $hint);
		}

		$appsToUpgrade = array_map(function (array $app): array {
			return [
				'id' => $app['id'],
				'name' => $app['name'],
				'version' => $app['version'],
				'oldVersion' => $this->appConfig->getValueString($app['id'], 'installed_version'),
			];
		}, $appManager->getAppsNeedingUpgrade($ocVersion));

		$params = [
			'appsToUpgrade' => $appsToUpgrade,
			'incompatibleAppsList' => $incompatibleDisabledApps,
			'isAppsOnlyUpgrade' => $isAppsOnlyUpgrade,
			'oldTheme' => $oldTheme,
			'productName' => self::getProductName(),
			'version' => $serverVersion->getVersionString(),
		];

		$this->initialState->provideInitialState('core', 'updaterView', 'admin');
		$this->initialState->provideInitialState('core', 'updateInfo', $params);
		return new TemplateResponse('', 'update', [], TemplateResponse::RENDER_AS_GUEST);
	}

	private static function getProductName(): string {
		$productName = 'Nextcloud';
		try {
			$defaults = new \OC_Defaults();
			$productName = $defaults->getName();
		} catch (Throwable $error) {
			// ignore
		}
		return $productName;
	}
}
