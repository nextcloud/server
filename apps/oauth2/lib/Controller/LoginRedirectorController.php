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
	 * Authorize the user
	 *
	 * @param string $client_id Client ID
	 * @param string $state State of the flow
	 * @param string $response_type Response type for the flow
	 * @param string $redirect_uri URI to redirect to after the flow (is only used for legacy ownCloud clients)
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
		string $redirect_uri = ''): TemplateResponse|RedirectResponse {
		try {
			$client = $this->clientMapper->getByIdentifier($client_id);
		} catch (ClientNotFoundException $e) {
			$params = [
				'content' => $this->l->t('Your client is not authorized to connect. Please inform the administrator of your client.'),
			];
			return new TemplateResponse('core', '404', $params, 'guest');
		}

		if ($response_type !== 'code') {
			//Fail
			$url = $client->getRedirectUri() . '?error=unsupported_response_type&state=' . $state;
			return new RedirectResponse($url);
		}

		$enableOcClients = $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);

		$providedRedirectUri = '';
		if ($enableOcClients && $client->getRedirectUri() === 'http://localhost:*') {
			$providedRedirectUri = $redirect_uri;
		}

		$this->session->set('oauth.state', $state);

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
