<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Template;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Authentication\Token\IProvider;
use OC\CapabilitiesManager;
use OC\Core\AppInfo\ConfigLexicon;
use OC\Files\FilenameValidator;
use OCA\Provisioning_API\Controller\AUserDataOCSController;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\Token\IToken;
use OCP\Constants;
use OCP\Defaults;
use OCP\Files\FileInfo;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\ILogger;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\Share\IManager as IShareManager;
use OCP\User\Backend\IPasswordConfirmationBackend;
use OCP\Util;

/**
 * Builds frontend bootstrap configuration for the web UI.
 *
 * This class collects server, user, sharing, localization, and theme settings,
 * exposes selected values through the initial state service, and renders the
 * JavaScript configuration payload consumed during page initialization.
 */
class JSConfigHelper {

	/**
	 * Backend class names for which password confirmation should be treated as unavailable.
	 *
	 * @var array<string, bool>
	 */
	private $passwordConfirmationExcludedBackends = [
		'user_saml' => true,
		'user_globalsiteselector' => true,
	];

	public function __construct(
		protected ServerVersion $serverVersion,
		protected IL10N $l,
		protected Defaults $defaults,
		protected IAppManager $appManager,
		protected ISession $session,
		protected ?IUser $currentUser,
		protected IConfig $config,
		protected readonly IAppConfig $appConfig,
		protected IGroupManager $groupManager,
		protected IniGetWrapper $iniWrapper,
		protected IURLGenerator $urlGenerator,
		protected CapabilitiesManager $capabilitiesManager,
		protected IInitialStateService $initialStateService,
		protected IProvider $tokenProvider,
		protected FilenameValidator $filenameValidator,
	) {
	}

	/**
	 * Builds the JavaScript configuration payload for page initialization.
	 *
	 * @return string JavaScript source containing global variable assignments.
	 */
	public function getConfig(): string {
		$userBackendAllowsPasswordConfirmation = true;
		if ($this->currentUser !== null) {
			$uid = $this->currentUser->getUID();

			$userBackend = $this->currentUser->getBackend();
			if ($userBackend instanceof IPasswordConfirmationBackend) {
				$userBackendAllowsPasswordConfirmation = $userBackend->canConfirmPassword($uid) && $this->canUserValidatePassword();
			} elseif (isset($this->passwordConfirmationExcludedBackends[$this->currentUser->getBackendClassName()])) {
				$userBackendAllowsPasswordConfirmation = false;
			} else {
				$userBackendAllowsPasswordConfirmation = $this->canUserValidatePassword();
			}
		} else {
			$uid = null;
		}

		// Build the map of enabled app IDs to their public web paths for the current context.
		$appWebPaths = [];

		if ($this->currentUser === null) {
			$enabledApps = $this->appManager->getEnabledApps();
		} else {
			$enabledApps = $this->appManager->getEnabledAppsForUser($this->currentUser);
		}

		foreach ($enabledApps as $app) {
			try {
				$appWebPaths[$app] = $this->appManager->getAppWebPath($app);
			} catch (AppPathNotFoundException $e) {
				$appWebPaths[$app] = false;
			}
		}

		$enableLinkPasswordByDefault = $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_PASSWORD_DEFAULT);
		$defaultExpireDateEnabled = $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_EXPIRE_DATE_DEFAULT);
		$defaultExpireDate = $enforceDefaultExpireDate = null;
		if ($defaultExpireDateEnabled) {
			$defaultExpireDate = (int)$this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
			$enforceDefaultExpireDate = $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_EXPIRE_DATE_ENFORCED);
		}
		$outgoingServer2serverShareEnabled = $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'yes';

		$defaultInternalExpireDateEnabled = $this->config->getAppValue('core', 'shareapi_default_internal_expire_date', 'no') === 'yes';
		$defaultInternalExpireDate = $defaultInternalExpireDateEnforced = null;
		if ($defaultInternalExpireDateEnabled) {
			$defaultInternalExpireDate = (int)$this->config->getAppValue('core', 'shareapi_internal_expire_after_n_days', '7');
			$defaultInternalExpireDateEnforced = $this->config->getAppValue('core', 'shareapi_enforce_internal_expire_date', 'no') === 'yes';
		}

		$defaultRemoteExpireDateEnabled = $this->config->getAppValue('core', 'shareapi_default_remote_expire_date', 'no') === 'yes';
		$defaultRemoteExpireDate = $defaultRemoteExpireDateEnforced = null;
		if ($defaultRemoteExpireDateEnabled) {
			$defaultRemoteExpireDate = (int)$this->config->getAppValue('core', 'shareapi_remote_expire_after_n_days', '7');
			$defaultRemoteExpireDateEnforced = $this->config->getAppValue('core', 'shareapi_enforce_remote_expire_date', 'no') === 'yes';
		}

		// Expose the data directory only when it is a child of the server root and the
		// current user is an admin; otherwise keep it hidden from the client.
		$dataDirectoryPrefixReplacementCount = 0;
		$relativeDataDirectory = str_replace(\OC::$SERVERROOT . '/', '', $this->config->getSystemValue('datadirectory', ''), $dataDirectoryPrefixReplacementCount);
		if ($dataDirectoryPrefixReplacementCount !== 1 || $uid === null || !$this->groupManager->isAdmin($uid)) {
			$relativeDataDirectory = false;
		}

		if ($this->currentUser instanceof IUser) {
			if ($this->canUserValidatePassword()) {
				$lastConfirmTimestamp = $this->session->get('last-password-confirm');
				if (!is_int($lastConfirmTimestamp)) {
					$lastConfirmTimestamp = 0;
				}
			} else {
				// Use a sentinel value so the frontend treats password confirmation as already satisfied
				// when this user/session cannot perform password validation.
				$lastConfirmTimestamp = PHP_INT_MAX;
			}
		} else {
			$lastConfirmTimestamp = 0;
		}

		$capabilities = $this->capabilitiesManager->getCapabilities(false, true);

		$firstDayOfWeek = $this->config->getUserValue($uid, 'core', AUserDataOCSController::USER_FIELD_FIRST_DAY_OF_WEEK, '');
		if ($firstDayOfWeek === '') {
			$firstDayOfWeek = (int)$this->l->l('firstday', null);
		} else {
			$firstDayOfWeek = (int)$firstDayOfWeek;
		}

		$coreConfig = [
			/** @deprecated 30.0.0 - use files capabilities instead */
			'blacklist_files_regex' => FileInfo::BLACKLIST_FILES_REGEX,
			/** @deprecated 30.0.0 - use files capabilities instead */
			'forbidden_filename_characters' => $this->filenameValidator->getForbiddenCharacters(),

			'auto_logout' => $this->config->getSystemValue('auto_logout', false),
			'loglevel' => $this->config->getSystemValue('loglevel_frontend',
				$this->config->getSystemValue('loglevel', ILogger::WARN)
			),
			'lost_password_link' => $this->config->getSystemValue('lost_password_link', null),
			'modRewriteWorking' => $this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true',
			'no_unsupported_browser_warning' => $this->config->getSystemValue('no_unsupported_browser_warning', false),
			'session_keepalive' => $this->config->getSystemValue('session_keepalive', true),
			'session_lifetime' => min($this->config->getSystemValue('session_lifetime', $this->iniWrapper->getNumeric('session.gc_maxlifetime')), $this->iniWrapper->getNumeric('session.gc_maxlifetime')),
			'sharing.maxAutocompleteResults' => max(0, $this->config->getSystemValueInt('sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT)),
			'sharing.minSearchStringLength' => $this->config->getSystemValueInt('sharing.minSearchStringLength', 0),
			'version' => implode('.', $this->serverVersion->getVersion()),
			'versionstring' => $this->serverVersion->getVersionString(),
			'enable_non-accessible_features' => $this->config->getSystemValueBool('enable_non-accessible_features', true),
		];

		$shareManager = Server::get(IShareManager::class);

		$legacyJsGlobals = [
			'_oc_debug' => $this->config->getSystemValue('debug', false) ? 'true' : 'false',
			'_oc_isadmin' => $uid !== null && $this->groupManager->isAdmin($uid) ? 'true' : 'false',
			'backendAllowsPasswordConfirmation' => $userBackendAllowsPasswordConfirmation ? 'true' : 'false',
			'oc_dataURL' => is_string($relativeDataDirectory) ? '"' . $relativeDataDirectory . '"' : 'false',
			'_oc_webroot' => '"' . \OC::$WEBROOT . '"',
			'_oc_appswebroots' => str_replace('\\/', '/', json_encode($appWebPaths)), // Ugly unescape slashes waiting for better solution
			'datepickerFormatDate' => json_encode($this->l->l('jsdate', null)),
			'nc_lastLogin' => $lastConfirmTimestamp,
			'nc_pageLoad' => time(),
			'dayNames' => json_encode([
				$this->l->t('Sunday'),
				$this->l->t('Monday'),
				$this->l->t('Tuesday'),
				$this->l->t('Wednesday'),
				$this->l->t('Thursday'),
				$this->l->t('Friday'),
				$this->l->t('Saturday')
			]),
			'dayNamesShort' => json_encode([
				$this->l->t('Sun.'),
				$this->l->t('Mon.'),
				$this->l->t('Tue.'),
				$this->l->t('Wed.'),
				$this->l->t('Thu.'),
				$this->l->t('Fri.'),
				$this->l->t('Sat.')
			]),
			'dayNamesMin' => json_encode([
				$this->l->t('Su'),
				$this->l->t('Mo'),
				$this->l->t('Tu'),
				$this->l->t('We'),
				$this->l->t('Th'),
				$this->l->t('Fr'),
				$this->l->t('Sa')
			]),
			'monthNames' => json_encode([
				$this->l->t('January'),
				$this->l->t('February'),
				$this->l->t('March'),
				$this->l->t('April'),
				$this->l->t('May'),
				$this->l->t('June'),
				$this->l->t('July'),
				$this->l->t('August'),
				$this->l->t('September'),
				$this->l->t('October'),
				$this->l->t('November'),
				$this->l->t('December')
			]),
			'monthNamesShort' => json_encode([
				$this->l->t('Jan.'),
				$this->l->t('Feb.'),
				$this->l->t('Mar.'),
				$this->l->t('Apr.'),
				$this->l->t('May.'),
				$this->l->t('Jun.'),
				$this->l->t('Jul.'),
				$this->l->t('Aug.'),
				$this->l->t('Sep.'),
				$this->l->t('Oct.'),
				$this->l->t('Nov.'),
				$this->l->t('Dec.')
			]),
			'firstDay' => json_encode($firstDayOfWeek),
			'_oc_config' => json_encode($coreConfig),
			'oc_appconfig' => json_encode([
				'core' => [
					'defaultExpireDateEnabled' => $defaultExpireDateEnabled,
					'defaultExpireDate' => $defaultExpireDate,
					'defaultExpireDateEnforced' => $enforceDefaultExpireDate,
					'enforcePasswordForPublicLink' => Util::isPublicLinkPasswordRequired(),
					'enableLinkPasswordByDefault' => $enableLinkPasswordByDefault,
					'sharingDisabledForUser' => $shareManager->sharingDisabledForUser($uid),
					'resharingAllowed' => $this->appConfig->getValueBool('core', 'shareapi_allow_resharing', true),
					'remoteShareAllowed' => $outgoingServer2serverShareEnabled,
					'federatedCloudShareDoc' => $this->urlGenerator->linkToDocs('user-sharing-federated'),
					'allowGroupSharing' => $shareManager->allowGroupSharing(),
					'defaultInternalExpireDateEnabled' => $defaultInternalExpireDateEnabled,
					'defaultInternalExpireDate' => $defaultInternalExpireDate,
					'defaultInternalExpireDateEnforced' => $defaultInternalExpireDateEnforced,
					'defaultRemoteExpireDateEnabled' => $defaultRemoteExpireDateEnabled,
					'defaultRemoteExpireDate' => $defaultRemoteExpireDate,
					'defaultRemoteExpireDateEnforced' => $defaultRemoteExpireDateEnforced,
				]
			]),
			'_theme' => json_encode([
				'entity' => $this->defaults->getEntity(),
				'name' => $this->defaults->getName(),
				'productName' => $this->defaults->getProductName(),
				'title' => $this->defaults->getTitle(),
				'baseUrl' => $this->defaults->getBaseUrl(),
				'syncClientUrl' => $this->defaults->getSyncClientUrl(),
				'docBaseUrl' => $this->defaults->getDocBaseUrl(),
				'docPlaceholderUrl' => $this->defaults->buildDocLinkToKey('PLACEHOLDER'),
				'slogan' => $this->defaults->getSlogan(),
				'logoClaim' => '',
				'folder' => \OC_Util::getTheme(),
			]),
		];

		if ($this->currentUser !== null) {
			$legacyJsGlobals['oc_userconfig'] = json_encode([
				'avatar' => [
					'version' => (int)$this->config->getUserValue($uid, 'avatar', 'version', 0),
					'generated' => $this->config->getUserValue($uid, 'avatar', 'generated', 'true') === 'true',
				]
			]);
		}

		// Provide structured initial state for modern consumers in addition to the legacy JS globals below.
		$this->initialStateService->provideInitialState('core', 'projects_enabled', $this->config->getSystemValueBool('projects.enabled', false));
		$this->initialStateService->provideInitialState('core', 'config', $coreConfig);
		$this->initialStateService->provideInitialState('core', 'capabilities', $capabilities);

		// Allow legacy hooks to amend the generated JavaScript globals before rendering.
		\OC_Hook::emit('\OCP\Config', 'js', ['array' => &$legacyJsGlobals]);

		$jsBootstrap = '';

		// Render the globals as legacy `var` assignments.
		foreach ($legacyJsGlobals as $globalName => $serializedValue) {
			$jsBootstrap .= 'var ' . $globalName . '=' . $serializedValue . ';' . PHP_EOL;
		}

		return $jsBootstrap;
	}

	/**
	 * Returns whether the current session token allows password validation.
	 *
	 * If the token cannot be resolved from the current session, this method falls
	 * back to `true` to avoid incorrectly disabling password confirmation flows.
	 */
	protected function canUserValidatePassword(): bool {
		try {
			$token = $this->tokenProvider->getToken($this->session->getId());
		} catch (ExpiredTokenException|WipeTokenException|InvalidTokenException|SessionNotAvailableException) {
			// If the session token cannot be inspected, keep password validation enabled by default.
			return true;
		}
		$scope = $token->getScopeAsArray();
		return !isset($scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION]) || $scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION] === false;
	}
}
