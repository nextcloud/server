<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\WebAuthn;

use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithms;
use GuzzleHttp\Psr7\ServerRequest;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialEntity;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

class Manager {
	/** @var CredentialRepository */
	private $repository;

	/** @var PublicKeyCredentialMapper */
	private $credentialMapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var IConfig */
	private $config;

	public function __construct(
		CredentialRepository $repository,
		PublicKeyCredentialMapper $credentialMapper,
		LoggerInterface $logger,
		IConfig $config,
	) {
		$this->repository = $repository;
		$this->credentialMapper = $credentialMapper;
		$this->logger = $logger;
		$this->config = $config;
	}

	public function startRegistration(IUser $user, string $serverHost): PublicKeyCredentialCreationOptions {
		$rpEntity = new PublicKeyCredentialRpEntity(
			'Nextcloud', //Name
			$this->stripPort($serverHost),  //ID
			null                            //Icon
		);

		$userEntity = new PublicKeyCredentialUserEntity(
			$user->getUID(),                             // Name
			$user->getUID(),                             // ID
			$user->getDisplayName()                      // Display name
			//            'https://foo.example.co/avatar/123e4567-e89b-12d3-a456-426655440000' //Icon
		);

		$challenge = random_bytes(32);

		$publicKeyCredentialParametersList = [
			new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
			new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256),
		];

		$timeout = 60000;

		$excludedPublicKeyDescriptors = [
		];

		$authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
			AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
			AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			null,
			false,
		);

		return new PublicKeyCredentialCreationOptions(
			$rpEntity,
			$userEntity,
			$challenge,
			$publicKeyCredentialParametersList,
			$authenticatorSelectionCriteria,
			PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			$excludedPublicKeyDescriptors,
			$timeout,
		);
	}

	public function finishRegister(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $name, string $data): PublicKeyCredentialEntity {
		$tokenBindingHandler = new TokenBindingNotSupportedHandler();

		$attestationStatementSupportManager = new AttestationStatementSupportManager();
		$attestationStatementSupportManager->add(new NoneAttestationStatementSupport());

		$attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
		$publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);

		// Extension Output Checker Handler
		$extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

		// Authenticator Attestation Response Validator
		$authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
			$attestationStatementSupportManager,
			$this->repository,
			$tokenBindingHandler,
			$extensionOutputCheckerHandler
		);
		$authenticatorAttestationResponseValidator->setLogger($this->logger);

		try {
			// Load the data
			$publicKeyCredential = $publicKeyCredentialLoader->load($data);
			$response = $publicKeyCredential->response;

			// Check if the response is an Authenticator Attestation Response
			if (!$response instanceof AuthenticatorAttestationResponse) {
				throw new \RuntimeException('Not an authenticator attestation response');
			}

			// Check the response against the request
			$request = ServerRequest::fromGlobals();

			$publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
				$response,
				$publicKeyCredentialCreationOptions,
				$request,
				['localhost'],
			);
		} catch (\Throwable $exception) {
			throw $exception;
		}

		// Persist the data
		$userVerification = $response->attestationObject->authData->isUserVerified();
		return $this->repository->saveAndReturnCredentialSource($publicKeyCredentialSource, $name, $userVerification);
	}

	private function stripPort(string $serverHost): string {
		return preg_replace('/(:\d+$)/', '', $serverHost);
	}

	public function startAuthentication(string $uid, string $serverHost): PublicKeyCredentialRequestOptions {
		// List of registered PublicKeyCredentialDescriptor classes associated to the user
		$userVerificationRequirement = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
		$registeredPublicKeyCredentialDescriptors = array_map(function (PublicKeyCredentialEntity $entity) use (&$userVerificationRequirement) {
			if ($entity->getUserVerification() !== true) {
				$userVerificationRequirement = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;
			}
			$credential = $entity->toPublicKeyCredentialSource();
			return new PublicKeyCredentialDescriptor(
				$credential->type,
				$credential->publicKeyCredentialId,
			);
		}, $this->credentialMapper->findAllForUid($uid));

		// Public Key Credential Request Options
		return new PublicKeyCredentialRequestOptions(
			random_bytes(32),                                                          // Challenge
			$this->stripPort($serverHost),                                             // Relying Party ID
			$registeredPublicKeyCredentialDescriptors,                                 // Registered PublicKeyCredentialDescriptor classes
			$userVerificationRequirement,
			60000,                                                                     // Timeout
		);
	}

	public function finishAuthentication(PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, string $data, string $uid) {
		$attestationStatementSupportManager = new AttestationStatementSupportManager();
		$attestationStatementSupportManager->add(new NoneAttestationStatementSupport());

		$attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
		$publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);

		$tokenBindingHandler = new TokenBindingNotSupportedHandler();
		$extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();
		$algorithmManager = new \Cose\Algorithm\Manager();
		$algorithmManager->add(new ES256());
		$algorithmManager->add(new RS256());

		$authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
			$this->repository,
			$tokenBindingHandler,
			$extensionOutputCheckerHandler,
			$algorithmManager,
		);
		$authenticatorAssertionResponseValidator->setLogger($this->logger);

		try {
			$this->logger->debug('Loading publickey credentials from: ' . $data);

			// Load the data
			$publicKeyCredential = $publicKeyCredentialLoader->load($data);
			$response = $publicKeyCredential->response;

			// Check if the response is an Authenticator Attestation Response
			if (!$response instanceof AuthenticatorAssertionResponse) {
				throw new \RuntimeException('Not an authenticator attestation response');
			}

			// Check the response against the request
			$request = ServerRequest::fromGlobals();

			$publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
				$publicKeyCredential->rawId,
				$response,
				$publicKeyCredentialRequestOptions,
				$request,
				$uid,
				['localhost'],
			);
		} catch (\Throwable $e) {
			throw $e;
		}

		return true;
	}

	public function deleteRegistration(IUser $user, int $id): void {
		try {
			$entry = $this->credentialMapper->findById($user->getUID(), $id);
		} catch (DoesNotExistException $e) {
			$this->logger->warning("WebAuthn device $id does not exist, can't delete it");
			return;
		}

		$this->credentialMapper->delete($entry);
	}

	public function isWebAuthnAvailable(): bool {
		if (!extension_loaded('bcmath')) {
			return false;
		}

		if (!extension_loaded('gmp')) {
			return false;
		}

		if (!$this->config->getSystemValueBool('auth.webauthn.enabled', true)) {
			return false;
		}

		return true;
	}
}
