<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Controller;

use OC\Core\Controller\ClientFlowLoginController;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;

#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
class LoginRedirectorController extends Controller {
	private const PKCE_STRING_PATTERN = '/^[A-Za-z0-9._~-]{43,128}$/';
	private const LEGACY_LOCALHOST_REDIRECT_PATTERN = '/^http:\/\/localhost:[0-9]+$/';

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param ClientMapper $clientMapper
	 * @param ISession $session
	 * @param IL10N $l
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private ClientMapper $clientMapper,
		private ISession $session,
		private IL10N $l,
		private ISecureRandom $random,
		private IAppConfig $appConfig,
		private IConfig $config,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 */
	private function buildErrorRedirectResponse(
		string $registeredRedirectUri,
		string $providedRedirectUri,
		string $error,
		string $state,
		?string $errorDescription = null,
	): RedirectResponse {
		$redirectUri = $registeredRedirectUri;
		$enableOcClients = $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);
		if ($enableOcClients && $redirectUri === 'http://localhost:*' && preg_match(self::LEGACY_LOCALHOST_REDIRECT_PATTERN, $providedRedirectUri) === 1) {
			$redirectUri = $providedRedirectUri;
		}

		$fragment = '';
		$fragmentPosition = strpos($redirectUri, '#');
		if ($fragmentPosition !== false) {
			$fragment = substr($redirectUri, $fragmentPosition);
			$redirectUri = substr($redirectUri, 0, $fragmentPosition);
		}

		$params = [
			'error' => $error,
		];
		if ($errorDescription !== null) {
			$params['error_description'] = $errorDescription;
		}
		$params['state'] = $state;

		$separator = str_contains($redirectUri, '?') ? '&' : '?';
		return new RedirectResponse($redirectUri . $separator . http_build_query($params) . $fragment);
	}

	/**
	 * Authorize the user
	 *
	 * @param string $client_id Client ID
	 * @param string $state State of the flow
	 * @param string $response_type Response type for the flow
	 * @param string $redirect_uri URI to redirect to after the flow (is only used for legacy ownCloud clients)
	 * @param string $code_challenge PKCE code challenge (optional, RFC 7636 format)
	 * @param string $code_challenge_method PKCE code challenge method. Only "S256" is supported. If omitted, RFC 7636 defaults to "plain", which is rejected.
	 * @return TemplateResponse<Http::STATUS_OK, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Client not found
	 * 303: Redirect to login URL
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[UseSession]
	public function authorize($client_id,
		$state,
		$response_type,
		string $redirect_uri = '',
		string $code_challenge = '',
		string $code_challenge_method = ''): TemplateResponse|RedirectResponse {
		try {
			$client = $this->clientMapper->getByIdentifier($client_id);
		} catch (ClientNotFoundException $e) {
			$params = [
				'content' => $this->l->t('Your client is not authorized to connect. Please inform the administrator of your client.'),
			];
			return new TemplateResponse('core', '404', $params, 'guest');
		}

		if ($response_type !== 'code') {
			return $this->buildErrorRedirectResponse($client->getRedirectUri(), $redirect_uri, 'unsupported_response_type', $state);
		}

		if ($code_challenge === '' && $code_challenge_method !== '') {
			return $this->buildErrorRedirectResponse($client->getRedirectUri(), $redirect_uri, 'invalid_request', $state, 'code_challenge required');
		}

		if ($code_challenge !== '' && preg_match(self::PKCE_STRING_PATTERN, $code_challenge) !== 1) {
			return $this->buildErrorRedirectResponse($client->getRedirectUri(), $redirect_uri, 'invalid_request', $state, 'Invalid code_challenge');
		}

		$effectiveCodeChallengeMethod = $code_challenge_method === '' && $code_challenge !== ''
			? 'plain'
			: $code_challenge_method;
		if ($effectiveCodeChallengeMethod !== '' && $effectiveCodeChallengeMethod !== 'S256') {
			return $this->buildErrorRedirectResponse($client->getRedirectUri(), $redirect_uri, 'invalid_request', $state, 'Transform algorithm not supported');
		}

		$enableOcClients = $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);

		$providedRedirectUri = '';
		if ($enableOcClients && $client->getRedirectUri() === 'http://localhost:*') {
			$providedRedirectUri = $redirect_uri;
		}

		$this->session->set('oauth.state', $state);
		$this->session->set('oauth.code_challenge', $code_challenge);
		$this->session->set('oauth.code_challenge_method', $effectiveCodeChallengeMethod);

		if (in_array($client->getName(), $this->appConfig->getValueArray('oauth2', 'skipAuthPickerApplications', []))) {
			/** @see ClientFlowLoginController::showAuthPickerPage **/
			$stateToken = $this->random->generate(
				64,
				ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS
			);
			$this->session->set(ClientFlowLoginController::STATE_NAME, $stateToken);
			$targetUrl = $this->urlGenerator->linkToRouteAbsolute(
				'core.ClientFlowLogin.grantPage',
				[
					'stateToken' => $stateToken,
					'clientIdentifier' => $client->getClientIdentifier(),
					'providedRedirectUri' => $providedRedirectUri,
				]
			);
		} else {
			$targetUrl = $this->urlGenerator->linkToRouteAbsolute(
				'core.ClientFlowLogin.showAuthPickerPage',
				[
					'clientIdentifier' => $client->getClientIdentifier(),
					'providedRedirectUri' => $providedRedirectUri,
				]
			);
		}
		return new RedirectResponse($targetUrl);
	}
}
