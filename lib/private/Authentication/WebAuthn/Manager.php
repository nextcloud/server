<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\WebAuthn;

use Cose\Algorithms;
use GuzzleHttp\Psr7\ServerRequest;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialEntity;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorData;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class Manager {
	private const int TIMEOUT = 60000;

	private const array SUPPORTED_ALGORITHMS = [
		// ECDSA using the P-256 curve and SHA-256; widely supported by hardware keys and platform authenticators.
		Algorithms::COSE_ALGORITHM_ES256,
		// EdDSA with the Ed25519 curve; modern and efficient signature algorithm, but less widely supported.
		Algorithms::COSE_ALGORITHM_EDDSA,
		// RSASSA-PSS with SHA-256; modern and more secure RSA alternative. (PSS padding is more secure than PKCS#1 v1.5)
		Algorithms::COSE_ALGORITHM_PS256,
		// RSA Signature with PKCS#1 v1.5 padding and SHA-256; legacy standard included for broader compatibility.
		Algorithms::COSE_ALGORITHM_RS256,
	];

	private WebauthnSerializerFactory $serializerFactory;

	public function __construct(
		private CredentialRepository $repository,
		private PublicKeyCredentialMapper $credentialMapper,
		private LoggerInterface $logger,
		private IConfig $config,
	) {
		$attestationStatementSupportManager = AttestationStatementSupportManager::create();
		$attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
		$this->serializerFactory = new WebauthnSerializerFactory($attestationStatementSupportManager);
	}

	/**
	 * Start a Webauthn registration
	 *
	 * @param IUser $user - The user for which the registration is being started
	 * @param string $serverHost - The server host (used to determine the relying party ID)
	 * @return string The registration options to be sent to the client, serialized as a JSON string {@see https://w3c.github.io/webauthn/#dictdef-publickeycredentialcreationoptionsjson}
	 */
	public function startRegistration(IUser $user, string $serverHost): string {
		$options = $this->getRegistrationOptions($user, $serverHost, random_bytes(32));
		$serializer = $this->serializerFactory->create();
		return $serializer->serialize(
			$options,
			'json',
			[
				AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
				JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
			],
		);
	}

	/**
	 * Finish the Webauthn registration
	 *
	 * @param string $registrationOptions - The registration options that were sent to the client, serialized as a JSON string {@see https://w3c.github.io/webauthn/#dictdef-publickeycredentialcreationoptionsjson}
	 * @param string $name - The name of the credential to be saved
	 * @param string $data - The data returned from the client, serialized as a JSON string {@see https://w3c.github.io/webauthn/#typedefdef-publickeycredentialjson}
	 * @throws RuntimeException - If the registration options or data are invalid
	 */
	public function finishRegister(string $registrationOptions, string $name, string $data): PublicKeyCredentialEntity {
		$csmFactory = new CeremonyStepManagerFactory();
		$creationCSM = $csmFactory->creationCeremony();
		$authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create($creationCSM);
		$authenticatorAttestationResponseValidator->setLogger($this->logger);

		try {
			$serializer = $this->serializerFactory->create();
			$registrationOptions = $serializer->deserialize($registrationOptions, PublicKeyCredentialCreationOptions::class, 'json');
			$publicKeyCredential = $serializer->deserialize($data, PublicKeyCredential::class, 'json');
			$response = $publicKeyCredential->response;

			// Check if the response is an Authenticator Attestation Response
			if (!$response instanceof AuthenticatorAttestationResponse) {
				throw new \RuntimeException('Not an authenticator attestation response');
			}

			// Check the response against the request
			$request = ServerRequest::fromGlobals();

			$publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
				$response,
				$registrationOptions,
				$request->getUri()->getHost(),
			);
		} catch (\UnexpectedValueException $exception) {
			throw new \RuntimeException('Invalid registration options or data', previous: $exception);
		}

		// Persist the data
		$userVerification = $response->attestationObject->authData->isUserVerified();
		return $this->repository->saveCredentialSource($publicKeyCredentialSource, $name, $userVerification);
	}

	/**
	 * Start Webauthn authentication
	 *
	 * @param string $uid - The user ID for which the authentication is being started
	 * @param string $serverHost - The server host (used to determine the relying party ID)
	 * @return string The authentication options to be sent to the client, serialized as a JSON string {@see https://w3c.github.io/webauthn/#dictdef-publickeycredentialrequestoptionsjson}
	 */
	public function startAuthentication(string $uid, string $serverHost): string {
		// List of registered PublicKeyCredentialDescriptor classes associated to the user
		$userVerificationRequirement = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
		$registeredPublicKeyCredentialDescriptors = array_map(function (PublicKeyCredentialEntity $entity) use (&$userVerificationRequirement) {
			if ($entity->getUserVerification() !== true) {
				$userVerificationRequirement = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;
			}
			$credential = $this->repository->mapToCredentialRecord($entity);
			return new PublicKeyCredentialDescriptor(
				$credential->type,
				$credential->publicKeyCredentialId,
			);
		}, $this->credentialMapper->findAllForUid($uid));

		// Public Key Credential Request Options
		$options = new PublicKeyCredentialRequestOptions(
			random_bytes(32),
			$this->stripPort($serverHost),
			$registeredPublicKeyCredentialDescriptors,
			$userVerificationRequirement,
			self::TIMEOUT,
		);

		$serializer = $this->serializerFactory->create();
		return $serializer->serialize(
			$options,
			'json',
			[
				AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
				JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
			]);
	}

	/**
	 * Finish authentication of a Webauthn request
	 * @param string $requestOptions - The authentication options that were sent to the client, serialized as a JSON string {@see https://w3c.github.io/webauthn/#dictdef-publickeycredentialrequestoptionsjson}
	 * @param string $data - The data returned from the client, serialized as a JSON string {@see https://w3c.github.io/webauthn/#typedefdef-publickeycredentialjson}
	 * @param string $uid - The user ID for which the authentication is being finished
	 */
	public function finishAuthentication(string $requestOptions, string $data, string $uid): AuthenticatorData {
		$csmFactory = new CeremonyStepManagerFactory();
		$assertionCSM = $csmFactory->requestCeremony();
		$authenticatorAttestationResponseValidator = AuthenticatorAssertionResponseValidator::create($assertionCSM);
		$authenticatorAttestationResponseValidator->setLogger($this->logger);

		try {
			$this->logger->debug('Loading publickey credentials from: ' . $data);

			// Load the data
			$serializer = $this->serializerFactory->create();
			$publicKeyCredentialRequestOptions = $serializer->deserialize($requestOptions, PublicKeyCredentialRequestOptions::class, 'json');
			$publicKeyCredential = $serializer->deserialize($data, PublicKeyCredential::class, 'json');
			$response = $publicKeyCredential->response;

			// Check if the response is an Authenticator Attestation Response
			if (!$response instanceof AuthenticatorAssertionResponse) {
				throw new \RuntimeException('Not an authenticator attestation response');
			}

			$record = $this->repository->findOneByCredentialId($publicKeyCredential->rawId);
			if ($record === null) {
				throw new \RuntimeException('No credential found for the given ID');
			}

			// Check the response against the request
			$request = ServerRequest::fromGlobals();

			$updatedRecord = $authenticatorAttestationResponseValidator->check(
				$record,
				$response,
				$publicKeyCredentialRequestOptions,
				$request->getUri()->getHost(),
				$uid,
			);
			$this->repository->saveCredentialSource($updatedRecord);
		} catch (\Throwable $e) {
			throw $e;
		}

		return $response->authenticatorData;
	}

	/**
	 * Delete a WebAuthn registration
	 *
	 * @param IUser $user - The user for which the registration is being deleted
	 * @param int $id - The ID of the registration to be deleted
	 */
	public function deleteRegistration(IUser $user, int $id): void {
		try {
			$entry = $this->credentialMapper->findById($user->getUID(), $id);
		} catch (DoesNotExistException $e) {
			$this->logger->warning("WebAuthn device $id does not exist, can't delete it");
			return;
		}

		$this->credentialMapper->delete($entry);
	}

	/**
	 * Check if WebAuthn is available
	 */
	public function isWebAuthnAvailable(): bool {
		if (!$this->config->getSystemValueBool('auth.webauthn.enabled', true)) {
			return false;
		}

		return true;
	}

	protected function getRegistrationOptions(IUser $user, string $serverHost, ?string $challenge = null): PublicKeyCredentialCreationOptions {
		$rpEntity = new PublicKeyCredentialRpEntity('Nextcloud', $this->stripPort($serverHost));
		$userEntity = new PublicKeyCredentialUserEntity(
			$user->getUID(),
			$user->getUID(),
			$user->getDisplayName(),
		);

		$publicKeyCredentialParametersList = array_map(
			fn (int $algorithm) => new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $algorithm),
			self::SUPPORTED_ALGORITHMS,
		);

		$authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
			AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
			AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			null,
		);

		return new PublicKeyCredentialCreationOptions(
			$rpEntity,
			$userEntity,
			$challenge,
			$publicKeyCredentialParametersList,
			$authenticatorSelectionCriteria,
			PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			timeout: self::TIMEOUT,
		);
	}

	private function stripPort(string $serverHost): string {
		return preg_replace('/(:\d+$)/', '', $serverHost);
	}
}
