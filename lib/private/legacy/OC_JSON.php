<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorAuthManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Server;

class OC_JSON {
	/**
	 * Check if the app is enabled, send json error msg if not
	 * @param string $app
	 * @deprecated 12.0.0 Use the AppFramework instead. It will automatically check if the app is enabled.
	 * @suppress PhanDeprecatedFunction
	 */
	public static function checkAppEnabled($app) {
		if (!Server::get(IAppManager::class)->isEnabledForUser($app)) {
			$l = \OC::$server->getL10N('lib');
			self::error([ 'data' => [ 'message' => $l->t('Application is not enabled'), 'error' => 'application_not_enabled' ]]);
			exit();
		}
	}

	/**
	 * Check if the user is logged in, send json error msg if not
	 * @deprecated 12.0.0 Use annotation based ACLs from the AppFramework instead
	 * @suppress PhanDeprecatedFunction
	 */
	public static function checkLoggedIn() {
		$twoFactorAuthManger = Server::get(TwoFactorAuthManager::class);
		if (!Server::get(IUserSession::class)->isLoggedIn()
			|| $twoFactorAuthManger->needsSecondFactor(Server::get(IUserSession::class)->getUser())) {
			$l = \OC::$server->getL10N('lib');
			http_response_code(Http::STATUS_UNAUTHORIZED);
			self::error([ 'data' => [ 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' ]]);
			exit();
		}
	}

	/**
	 * Check an ajax get/post call if the request token is valid, send json error msg if not.
	 * @deprecated 12.0.0 Use annotation based CSRF checks from the AppFramework instead
	 * @suppress PhanDeprecatedFunction
	 */
	public static function callCheck() {
		if (!Server::get(IRequest::class)->passesStrictCookieCheck()) {
			header('Location: ' . \OC::$WEBROOT);
			exit();
		}

		if (!Server::get(IRequest::class)->passesCSRFCheck()) {
			$l = \OC::$server->getL10N('lib');
			self::error([ 'data' => [ 'message' => $l->t('Token expired. Please reload page.'), 'error' => 'token_expired' ]]);
			exit();
		}
	}

	/**
	 * Check if the user is a admin, send json error msg if not.
	 * @deprecated 12.0.0 Use annotation based ACLs from the AppFramework instead
	 * @suppress PhanDeprecatedFunction
	 */
	public static function checkAdminUser() {
		if (!OC_User::isAdminUser(OC_User::getUser())) {
			$l = \OC::$server->getL10N('lib');
			self::error([ 'data' => [ 'message' => $l->t('Authentication error'), 'error' => 'authentication_error' ]]);
			exit();
		}
	}

	/**
	 * Send json error msg
	 * @deprecated 12.0.0 Use a AppFramework JSONResponse instead
	 * @suppress PhanDeprecatedFunction
	 */
	public static function error($data = []) {
		$data['status'] = 'error';
		header('Content-Type: application/json; charset=utf-8');
		echo self::encode($data);
	}

	/**
	 * Send json success msg
	 * @deprecated 12.0.0 Use a AppFramework JSONResponse instead
	 * @suppress PhanDeprecatedFunction
	 */
	public static function success($data = []) {
		$data['status'] = 'success';
		header('Content-Type: application/json; charset=utf-8');
		echo self::encode($data);
	}

	/**
	 * Encode JSON
	 * @deprecated 12.0.0 Use a AppFramework JSONResponse instead
	 *
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape html
	 */
	private static function encode($data) {
		return json_encode($data, JSON_HEX_TAG);
	}
}
