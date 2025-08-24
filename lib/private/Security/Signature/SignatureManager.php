<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature;

use NCU\Security\Signature\Enum\SignatoryType;
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\Exceptions\IncomingRequestException;
use NCU\Security\Signature\Exceptions\InvalidKeyOriginException;
use NCU\Security\Signature\Exceptions\InvalidSignatureException;
use NCU\Security\Signature\Exceptions\SignatoryConflictException;
use NCU\Security\Signature\Exceptions\SignatoryException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use NCU\Security\Signature\Exceptions\SignatureNotFoundException;
use NCU\Security\Signature\IIncomingSignedRequest;
use NCU\Security\Signature\IOutgoingSignedRequest;
use NCU\Security\Signature\ISignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Model\Signatory;
use OC\Security\Signature\Db\SignatoryMapper;
use OC\Security\Signature\Model\IncomingSignedRequest;
use OC\Security\Signature\Model\OutgoingSignedRequest;
use OCP\DB\Exception as DBException;
use OCP\IAppConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * ISignatureManager is a service integrated to core that provide tools
 * to set/get authenticity of/from outgoing/incoming request.
 *
 * Quick description of the signature, added to the headers
 * {
 *     "(request-target)": "post /path",
 *     "content-length": 385,
 *     "date": "Mon, 08 Jul 2024 14:16:20 GMT",
 *     "digest": "SHA-256=U7gNVUQiixe5BRbp4Tg0xCZMTcSWXXUZI2\\/xtHM40S0=",
 *     "host": "hostname.of.the.recipient",
 *     "Signature": "keyId=\"https://author.hostname/key\",algorithm=\"sha256\",headers=\"content-length
 * date digest host\",signature=\"DzN12OCS1rsA[...]o0VmxjQooRo6HHabg==\""
 * }
 *
 * 'content-length' is the total length of the data/content
 * 'date' is the datetime the request have been initiated
 * 'digest' is a checksum of the data/content
 * 'host' is the hostname of the recipient of the request (remote when signing outgoing request, local on
 * incoming request)
 * 'Signature' contains the signature generated using the private key, and metadata:
 *    - 'keyId' is a unique id, formatted as an url. hostname is used to retrieve the public key via custom
 * discovery
 *    - 'algorithm' define the algorithm used to generate signature
 *    - 'headers' contains a list of element used during the generation of the signature
 *    - 'signature' is the encrypted string, using local private key, of an array containing elements
 *      listed in 'headers' and their value. Some elements (content-length date digest host) are mandatory
 *      to ensure authenticity override protection.
 *
 * @since 31.0.0
 */
class SignatureManager implements ISignatureManager {
	public const DATE_HEADER = 'D, d M Y H:i:s T';
	public const DATE_TTL = 300;
	public const SIGNATORY_TTL = 86400 * 3;
	public const BODY_MAXSIZE = 50000; // max size of the payload of the request
	public const APPCONFIG_IDENTITY = 'security.signature.identity';

	public function __construct(
		private readonly IRequest $request,
		private readonly SignatoryMapper $mapper,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @param ISignatoryManager $signatoryManager used to get details about remote instance
	 * @param string|null $body if NULL, body will be extracted from php://input
	 *
	 * @return IIncomingSignedRequest
	 * @throws IncomingRequestException if anything looks wrong with the incoming request
	 * @throws SignatureNotFoundException if incoming request is not signed
	 * @throws SignatureException if signature could not be confirmed
	 * @since 31.0.0
	 */
	public function getIncomingSignedRequest(
		ISignatoryManager $signatoryManager,
		?string $body = null,
	): IIncomingSignedRequest {
		$body = $body ?? file_get_contents('php://input');
		$options = $signatoryManager->getOptions();
		if (strlen($body) > ($options['bodyMaxSize'] ?? self::BODY_MAXSIZE)) {
			throw new IncomingRequestException('content of request is too big');
		}

		// generate IncomingSignedRequest based on body and request
		$signedRequest = new IncomingSignedRequest($body, $this->request, $options);

		try {
			// confirm the validity of content and identity of the incoming request
			$this->confirmIncomingRequestSignature($signedRequest, $signatoryManager, $options['ttlSignatory'] ?? self::SIGNATORY_TTL);
		} catch (SignatureException $e) {
			$this->logger->warning(
				'signature could not be verified', [
					'exception' => $e,
					'signedRequest' => $signedRequest,
					'signatoryManager' => get_class($signatoryManager)
				]
			);
			throw $e;
		}

		return $signedRequest;
	}

	/**
	 * confirm that the Signature is signed using the correct private key, using
	 * clear version of the Signature and the public key linked to the keyId
	 *
	 * @param IIncomingSignedRequest $signedRequest
	 * @param ISignatoryManager $signatoryManager
	 *
	 * @throws SignatoryNotFoundException
	 * @throws SignatureException
	 */
	private function confirmIncomingRequestSignature(
		IIncomingSignedRequest $signedRequest,
		ISignatoryManager $signatoryManager,
		int $ttlSignatory,
	): void {
		$knownSignatory = null;
		try {
			$knownSignatory = $this->getStoredSignatory($signedRequest->getKeyId());
			// refreshing ttl and compare with previous public key
			if ($ttlSignatory > 0 && $knownSignatory->getLastUpdated() < (time() - $ttlSignatory)) {
				$signatory = $this->getSaneRemoteSignatory($signatoryManager, $signedRequest);
				$this->updateSignatoryMetadata($signatory);
				$knownSignatory->setMetadata($signatory->getMetadata() ?? []);
			}

			$signedRequest->setSignatory($knownSignatory);
			$signedRequest->verify();
		} catch (InvalidKeyOriginException $e) {
			throw $e; // issue while requesting remote instance also means there is no 2nd try
		} catch (SignatoryNotFoundException) {
			// if no signatory in cache, we retrieve the one from the remote instance (using
			// $signatoryManager), check its validity with current signature and store it
			$signatory = $this->getSaneRemoteSignatory($signatoryManager, $signedRequest);
			$signedRequest->setSignatory($signatory);
			$signedRequest->verify();
			$this->storeSignatory($signatory);
		} catch (SignatureException) {
			// if public key (from cache) is not valid, we try to refresh it (based on SignatoryType)
			try {
				$signatory = $this->getSaneRemoteSignatory($signatoryManager, $signedRequest);
			} catch (SignatoryNotFoundException $e) {
				$this->manageDeprecatedSignatory($knownSignatory);
				throw $e;
			}

			$signedRequest->setSignatory($signatory);
			try {
				$signedRequest->verify();
			} catch (InvalidSignatureException $e) {
				$this->logger->debug('signature issue', ['signed' => $signedRequest, 'exception' => $e]);
				throw $e;
			}

			$this->storeSignatory($signatory);
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @param ISignatoryManager $signatoryManager
	 * @param string $content body to be signed
	 * @param string $method needed in the signature
	 * @param string $uri needed in the signature
	 *
	 * @return IOutgoingSignedRequest
	 * @throws IdentityNotFoundException
	 * @throws SignatoryException
	 * @throws SignatoryNotFoundException
	 * @since 31.0.0
	 */
	public function getOutgoingSignedRequest(
		ISignatoryManager $signatoryManager,
		string $content,
		string $method,
		string $uri,
	): IOutgoingSignedRequest {
		$signedRequest = new OutgoingSignedRequest(
			$content,
			$signatoryManager,
			$this->extractIdentityFromUri($uri),
			$method,
			parse_url($uri, PHP_URL_PATH) ?? '/'
		);

		$signedRequest->sign();

		return $signedRequest;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ISignatoryManager $signatoryManager
	 * @param array $payload original payload, will be used to sign and completed with new headers with
	 *                       signature elements
	 * @param string $method needed in the signature
	 * @param string $uri needed in the signature
	 *
	 * @return array new payload to be sent, including original payload and signature elements in headers
	 * @since 31.0.0
	 */
	public function signOutgoingRequestIClientPayload(
		ISignatoryManager $signatoryManager,
		array $payload,
		string $method,
		string $uri,
	): array {
		$signedRequest = $this->getOutgoingSignedRequest($signatoryManager, $payload['body'], $method, $uri);
		$payload['headers'] = array_merge($payload['headers'], $signedRequest->getHeaders());

		return $payload;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $host remote host
	 * @param string $account linked account, should be used when multiple signature can exist for the same
	 *                        host
	 *
	 * @return Signatory
	 * @throws SignatoryNotFoundException if entry does not exist in local database
	 * @since 31.0.0
	 */
	public function getSignatory(string $host, string $account = ''): Signatory {
		return $this->mapper->getByHost($host, $account);
	}


	/**
	 * @inheritDoc
	 *
	 * keyId is set using app config 'core/security.signature.identity'
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws IdentityNotFoundException is identity is not set in app config
	 * @since 31.0.0
	 */
	public function generateKeyIdFromConfig(string $path): string {
		if (!$this->appConfig->hasKey('core', self::APPCONFIG_IDENTITY, true)) {
			throw new IdentityNotFoundException(self::APPCONFIG_IDENTITY . ' not set');
		}

		$identity = trim($this->appConfig->getValueString('core', self::APPCONFIG_IDENTITY, lazy: true), '/');

		return 'https://' . $identity . '/' . ltrim($path, '/');
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $uri
	 *
	 * @return string
	 * @throws IdentityNotFoundException if identity cannot be extracted
	 * @since 31.0.0
	 */
	public function extractIdentityFromUri(string $uri): string {
		return Signatory::extractIdentityFromUri($uri);
	}

	/**
	 * get remote signatory using the ISignatoryManager
	 * and confirm the validity of the keyId
	 *
	 * @param ISignatoryManager $signatoryManager
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return Signatory
	 * @throws InvalidKeyOriginException
	 * @throws SignatoryNotFoundException
	 * @see ISignatoryManager::getRemoteSignatory
	 */
	private function getSaneRemoteSignatory(
		ISignatoryManager $signatoryManager,
		IIncomingSignedRequest $signedRequest,
	): Signatory {
		$signatory = $signatoryManager->getRemoteSignatory($signedRequest->getOrigin());
		if ($signatory === null) {
			throw new SignatoryNotFoundException('empty result from getRemoteSignatory');
		}
		try {
			if ($signatory->getKeyId() !== $signedRequest->getKeyId()) {
				throw new InvalidKeyOriginException('keyId from signatory not related to the one from request');
			}
		} catch (SignatureElementNotFoundException) {
			throw new InvalidKeyOriginException('missing keyId');
		}
		$signatory->setProviderId($signatoryManager->getProviderId());

		return $signatory;
	}

	/**
	 * @param string $keyId
	 *
	 * @return Signatory
	 * @throws SignatoryNotFoundException
	 */
	private function getStoredSignatory(string $keyId): Signatory {
		return $this->mapper->getByKeyId($keyId);
	}

	/**
	 * @param Signatory $signatory
	 */
	private function storeSignatory(Signatory $signatory): void {
		try {
			$this->insertSignatory($signatory);
		} catch (DBException $e) {
			if ($e->getReason() !== DBException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$this->logger->warning('exception while storing signature', ['exception' => $e]);
				throw $e;
			}

			try {
				$this->updateKnownSignatory($signatory);
			} catch (SignatoryNotFoundException $e) {
				$this->logger->warning('strange behavior, signatory not found ?', ['exception' => $e]);
			}
		}
	}

	/**
	 * @param Signatory $signatory
	 */
	private function insertSignatory(Signatory $signatory): void {
		$time = time();
		$signatory->setCreation($time);
		$signatory->setLastUpdated($time);
		$signatory->setMetadata($signatory->getMetadata() ?? []); // trigger insert on field metadata using current or default value
		$this->mapper->insert($signatory);
	}

	/**
	 * @param Signatory $signatory
	 *
	 * @throws SignatoryNotFoundException
	 * @throws SignatoryConflictException
	 */
	private function updateKnownSignatory(Signatory $signatory): void {
		$knownSignatory = $this->getStoredSignatory($signatory->getKeyId());
		switch ($signatory->getType()) {
			case SignatoryType::FORGIVABLE:
				$this->deleteSignatory($knownSignatory->getKeyId());
				$this->insertSignatory($signatory);
				return;

			case SignatoryType::REFRESHABLE:
				$this->updateSignatoryPublicKey($signatory);
				$this->updateSignatoryMetadata($signatory);
				break;

			case SignatoryType::TRUSTED:
				// TODO: send notice to admin
				throw new SignatoryConflictException();

			case SignatoryType::STATIC:
				// TODO: send warning to admin
				throw new SignatoryConflictException();
		}
	}

	/**
	 * This is called when a remote signatory does not exist anymore
	 *
	 * @param Signatory|null $knownSignatory NULL is not known
	 *
	 * @throws SignatoryConflictException
	 * @throws SignatoryNotFoundException
	 */
	private function manageDeprecatedSignatory(?Signatory $knownSignatory): void {
		switch ($knownSignatory?->getType()) {
			case null: // unknown in local database
			case SignatoryType::FORGIVABLE: // who cares ?
				throw new SignatoryNotFoundException(); // meaning we just return the correct exception

			case SignatoryType::REFRESHABLE:
				// TODO: send notice to admin
				throw new SignatoryConflictException(); // while it can be refreshed, it must exist

			case SignatoryType::TRUSTED:
			case SignatoryType::STATIC:
				// TODO: send warning to admin
				throw new SignatoryConflictException(); // no way.
		}
	}


	private function updateSignatoryPublicKey(Signatory $signatory): void {
		$this->mapper->updatePublicKey($signatory);
	}

	private function updateSignatoryMetadata(Signatory $signatory): void {
		$this->mapper->updateMetadata($signatory);
	}

	private function deleteSignatory(string $keyId): void {
		$this->mapper->deleteByKeyId($keyId);
	}
}
