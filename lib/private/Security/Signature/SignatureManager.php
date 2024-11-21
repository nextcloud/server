<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature;

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
use NCU\Security\Signature\ISignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Model\IIncomingSignedRequest;
use NCU\Security\Signature\Model\IOutgoingSignedRequest;
use NCU\Security\Signature\Model\ISignatory;
use NCU\Security\Signature\Model\SignatoryType;
use NCU\Security\Signature\SignatureAlgorithm;
use OC\Security\Signature\Model\IncomingSignedRequest;
use OC\Security\Signature\Model\OutgoingSignedRequest;
use OC\Security\Signature\Model\Signatory;
use OCP\DB\Exception as DBException;
use OCP\IAppConfig;
use OCP\IDBConnection;
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
	public const TABLE_SIGNATORIES = 'sec_signatory';
	public const BODY_MAXSIZE = 50000; // max size of the payload of the request
	public const APPCONFIG_IDENTITY = 'security.signature.identity';

	public function __construct(
		private readonly IRequest $request,
		private readonly IDBConnection $connection,
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
			// we set origin based on the keyId defined in the Signature header of the request
			$signedRequest->setOrigin($this->extractIdentityFromUri($signedRequest->getSignatureElement('keyId')));
		} catch (IdentityNotFoundException $e) {
			throw new IncomingRequestException($e->getMessage());
		}

		try {
			// confirm the validity of content and identity of the incoming request
			$this->generateExpectedClearSignatureFromRequest($signedRequest, $options['extraSignatureHeaders'] ?? []);
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
	 * generating the expected signature (clear version) sent by the remote instance
	 * based on the data available in the Signature header.
	 *
	 * @param IIncomingSignedRequest $signedRequest
	 * @param array $extraSignatureHeaders
	 *
	 * @throws SignatureException
	 */
	private function generateExpectedClearSignatureFromRequest(
		IIncomingSignedRequest $signedRequest,
		array $extraSignatureHeaders = [],
	): void {
		$request = $signedRequest->getRequest();
		$usedHeaders = explode(' ', $signedRequest->getSignatureElement('headers'));
		$neededHeaders = array_merge(['date', 'host', 'content-length', 'digest'], array_keys($extraSignatureHeaders));

		$missingHeaders = array_diff($neededHeaders, $usedHeaders);
		if ($missingHeaders !== []) {
			throw new SignatureException('missing entries in Signature.headers: ' . json_encode($missingHeaders));
		}

		$estimated = ['(request-target): ' . strtolower($request->getMethod()) . ' ' . $request->getRequestUri()];
		foreach ($usedHeaders as $key) {
			if ($key === '(request-target)') {
				continue;
			}
			$value = (strtolower($key) === 'host') ? $request->getServerHost() : $request->getHeader($key);
			if ($value === '') {
				throw new SignatureException('missing header ' . $key . ' in request');
			}

			$estimated[] = $key . ': ' . $value;
		}

		$signedRequest->setClearSignature(implode("\n", $estimated));
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
				$knownSignatory->setMetadata($signatory->getMetadata());
			}

			$signedRequest->setSignatory($knownSignatory);
			$this->verifySignedRequest($signedRequest);
		} catch (InvalidKeyOriginException $e) {
			throw $e; // issue while requesting remote instance also means there is no 2nd try
		} catch (SignatoryNotFoundException) {
			// if no signatory in cache, we retrieve the one from the remote instance (using
			// $signatoryManager), check its validity with current signature and store it
			$signatory = $this->getSaneRemoteSignatory($signatoryManager, $signedRequest);
			$signedRequest->setSignatory($signatory);
			$this->verifySignedRequest($signedRequest);
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
			$this->verifySignedRequest($signedRequest);
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

		$this->signOutgoingRequest($signedRequest);

		return $signedRequest;
	}

	/**
	 * signing clear version of the Signature header
	 *
	 * @param IOutgoingSignedRequest $signedRequest
	 *
	 * @throws SignatoryException
	 * @throws SignatoryNotFoundException
	 */
	private function signOutgoingRequest(IOutgoingSignedRequest $signedRequest): void {
		$clear = $signedRequest->getClearSignature();
		$signed = $this->signString($clear, $signedRequest->getSignatory()->getPrivateKey(), $signedRequest->getAlgorithm());

		$signatory = $signedRequest->getSignatory();
		$signatureElements = [
			'keyId="' . $signatory->getKeyId() . '"',
			'algorithm="' . $signedRequest->getAlgorithm()->value . '"',
			'headers="' . implode(' ', $signedRequest->getHeaderList()) . '"',
			'signature="' . $signed . '"'
		];

		$signedRequest->setSignedSignature($signed);
		$signedRequest->addHeader('Signature', implode(',', $signatureElements));
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
	 * @return ISignatory
	 * @throws SignatoryNotFoundException if entry does not exist in local database
	 * @since 31.0.0
	 */
	public function searchSignatory(string $host, string $account = ''): ISignatory {
		$qb = $this->connection->getQueryBuilder();
		$qb->select(
			'id', 'provider_id', 'host', 'account', 'key_id', 'key_id_sum', 'public_key', 'metadata', 'type',
			'status', 'creation', 'last_updated'
		);
		$qb->from(self::TABLE_SIGNATORIES);
		$qb->where($qb->expr()->eq('host', $qb->createNamedParameter($host)));
		$qb->andWhere($qb->expr()->eq('account', $qb->createNamedParameter($account)));

		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if (!$row) {
			throw new SignatoryNotFoundException('no signatory found');
		}

		$signature = new Signatory($row['key_id'], $row['public_key']);

		return $signature->importFromDatabase($row);
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
		$identity = parse_url($uri, PHP_URL_HOST);
		$port = parse_url($uri, PHP_URL_PORT);
		if ($identity === null || $identity === false) {
			throw new IdentityNotFoundException('cannot extract identity from ' . $uri);
		}

		if ($port !== null && $port !== false) {
			$identity .= ':' . $port;
		}

		return $identity;
	}

	/**
	 * get remote signatory using the ISignatoryManager
	 * and confirm the validity of the keyId
	 *
	 * @param ISignatoryManager $signatoryManager
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return ISignatory
	 * @throws InvalidKeyOriginException
	 * @throws SignatoryNotFoundException
	 * @see ISignatoryManager::getRemoteSignatory
	 */
	private function getSaneRemoteSignatory(
		ISignatoryManager $signatoryManager,
		IIncomingSignedRequest $signedRequest,
	): ISignatory {
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

		return $signatory->setProviderId($signatoryManager->getProviderId());
	}

	/**
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return void
	 * @throws SignatureException
	 * @throws SignatoryNotFoundException
	 */
	private function verifySignedRequest(IIncomingSignedRequest $signedRequest): void {
		$publicKey = $signedRequest->getSignatory()->getPublicKey();
		if ($publicKey === '') {
			throw new SignatoryNotFoundException('empty public key');
		}

		try {
			$this->verifyString(
				$signedRequest->getClearSignature(),
				$signedRequest->getSignedSignature(),
				$publicKey,
				SignatureAlgorithm::tryFrom($signedRequest->getSignatureElement('algorithm')) ?? SignatureAlgorithm::SHA256
			);
		} catch (InvalidSignatureException $e) {
			$this->logger->debug('signature issue', ['signed' => $signedRequest, 'exception' => $e]);
			throw $e;
		}
	}

	/**
	 * @param string $clear
	 * @param string $privateKey
	 * @param SignatureAlgorithm $algorithm
	 *
	 * @return string
	 * @throws SignatoryException
	 */
	private function signString(string $clear, string $privateKey, SignatureAlgorithm $algorithm): string {
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		openssl_sign($clear, $signed, $privateKey, $algorithm->value);

		return base64_encode($signed);
	}

	/**
	 * @param string $clear
	 * @param string $encoded
	 * @param string $publicKey
	 * @param SignatureAlgorithm $algorithm
	 *
	 * @throws InvalidSignatureException
	 */
	private function verifyString(
		string $clear,
		string $encoded,
		string $publicKey,
		SignatureAlgorithm $algorithm = SignatureAlgorithm::SHA256,
	): void {
		$signed = base64_decode($encoded);
		if (openssl_verify($clear, $signed, $publicKey, $algorithm->value) !== 1) {
			throw new InvalidSignatureException('signature issue');
		}
	}

	/**
	 * @param string $keyId
	 *
	 * @return ISignatory
	 * @throws SignatoryNotFoundException
	 */
	private function getStoredSignatory(string $keyId): ISignatory {
		$qb = $this->connection->getQueryBuilder();
		$qb->select(
			'id', 'provider_id', 'host', 'account', 'key_id', 'key_id_sum', 'public_key', 'metadata', 'type',
			'status', 'creation', 'last_updated'
		);
		$qb->from(self::TABLE_SIGNATORIES);
		$qb->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($keyId))));

		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if (!$row) {
			throw new SignatoryNotFoundException('no signatory found in local');
		}

		$signature = new Signatory($row['key_id'], $row['public_key']);
		$signature->importFromDatabase($row);

		return $signature;
	}

	/**
	 * @param ISignatory $signatory
	 */
	private function storeSignatory(ISignatory $signatory): void {
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
	 * @param ISignatory $signatory
	 * @throws DBException
	 */
	private function insertSignatory(ISignatory $signatory): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert(self::TABLE_SIGNATORIES)
			->setValue('provider_id', $qb->createNamedParameter($signatory->getProviderId()))
			->setValue('host', $qb->createNamedParameter($this->extractIdentityFromUri($signatory->getKeyId())))
			->setValue('account', $qb->createNamedParameter($signatory->getAccount()))
			->setValue('key_id', $qb->createNamedParameter($signatory->getKeyId()))
			->setValue('key_id_sum', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId())))
			->setValue('public_key', $qb->createNamedParameter($signatory->getPublicKey()))
			->setValue('metadata', $qb->createNamedParameter(json_encode($signatory->getMetadata())))
			->setValue('type', $qb->createNamedParameter($signatory->getType()->value))
			->setValue('status', $qb->createNamedParameter($signatory->getStatus()->value))
			->setValue('creation', $qb->createNamedParameter(time()))
			->setValue('last_updated', $qb->createNamedParameter(time()));

		$qb->executeStatement();
	}

	/**
	 * @param ISignatory $signatory
	 *
	 * @throws SignatoryNotFoundException
	 * @throws SignatoryConflictException
	 */
	private function updateKnownSignatory(ISignatory $signatory): void {
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
	 * @param ISignatory|null $knownSignatory NULL is not known
	 *
	 * @throws SignatoryConflictException
	 * @throws SignatoryNotFoundException
	 */
	private function manageDeprecatedSignatory(?ISignatory $knownSignatory): void {
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


	private function updateSignatoryPublicKey(ISignatory $signatory): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE_SIGNATORIES)
			->set('signatory', $qb->createNamedParameter($signatory->getPublicKey()))
			->set('last_updated', $qb->createNamedParameter(time()));

		$qb->where(
			$qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId())))
		);
		$qb->executeStatement();
	}

	private function updateSignatoryMetadata(ISignatory $signatory): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE_SIGNATORIES)
			->set('metadata', $qb->createNamedParameter(json_encode($signatory->getMetadata())))
			->set('last_updated', $qb->createNamedParameter(time()));

		$qb->where(
			$qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId())))
		);
		$qb->executeStatement();
	}

	private function deleteSignatory(string $keyId): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete(self::TABLE_SIGNATORIES)
			->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($keyId))));
		$qb->executeStatement();
	}

	private function hashKeyId(string $keyId): string {
		return hash('sha256', $keyId);
	}
}
