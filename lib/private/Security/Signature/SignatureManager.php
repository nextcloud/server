<?php

declare(strict_types=1);

namespace OC\Security\Signature;

use OC\Security\Signature\Model\IncomingSignedRequest;
use OC\Security\Signature\Model\OutgoingSignedRequest;
use OC\Security\Signature\Model\Signatory;
use OCP\DB\Exception as DBException;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\InvalidKeyOriginException;
use OCP\Security\Signature\Exceptions\InvalidSignatureException;
use OCP\Security\Signature\Exceptions\SignatoryConflictException;
use OCP\Security\Signature\Exceptions\SignatoryException;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\ISignatoryManager;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\IIncomingSignedRequest;
use OCP\Security\Signature\Model\IOutgoingSignedRequest;
use OCP\Security\Signature\Model\ISignatory;
use OCP\Security\Signature\Model\SignatoryType;
use OCP\Security\Signature\SignatureAlgorithm;
use Psr\Log\LoggerInterface;

class SignatureManager implements ISignatureManager {
	private const DATE_HEADER = 'D, d M Y H:i:s T';
	private const DATE_OBJECT = 'Y-m-d\TH:i:s\Z';
	private const DATE_TTL = 300;
	private const SIGNATORY_TTL = 86400*3;
	private const TABLE_SIGNATORIES = 'sec_signatory';
	private const BODY_MAXSIZE = 50000; // max size of the payload of the request

	public function __construct(
		private readonly IRequest $request,
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @param ISignatoryManager $signatoryManager
	 * @param string|null $body if NULL content will be extracted from php://input
	 *
	 * @return IIncomingSignedRequest
	 * @throws IncomingRequestException
	 * @throws SignatureException
	 */
	public function getIncomingSignedRequest(
		ISignatoryManager $signatoryManager,
		?string $body = null
	): IIncomingSignedRequest {
		$body = $body ?? file_get_contents('php://input');
		$this->logger->debug('[<<] incoming signed request', ['body length' => strlen($body)]);

		if (strlen($body) > self::BODY_MAXSIZE) {
			throw new IncomingRequestException('content of request is too big');
		}

		$signedRequest = new IncomingSignedRequest($body);
		$signedRequest->setRequest($this->request);
		$options = $signatoryManager->getOptions();

		try {
			$this->verifyIncomingRequestTime($signedRequest, $options['ttl'] ?? self::DATE_TTL);
			$this->verifyIncomingRequestContent($signedRequest);
			$this->prepIncomingSignatureHeader($signedRequest);
			$this->verifyIncomingSignatureHeader($signedRequest);
			$this->prepEstimatedSignature($signedRequest, $options['extraSignatureHeaders'] ?? []);
			$this->verifyIncomingRequestSignature($signedRequest, $signatoryManager, $options['ttlSignatory'] ?? self::SIGNATORY_TTL);
		} catch (SignatureException $e) {
			$this->logger->warning(
				'signature could not be verified', [
				'exception' => $e, 'signedRequest' => $signedRequest,
				'signatoryManager' => get_class($signatoryManager)
			]
			);
			throw $e;
		}

		return $signedRequest;
	}

	public function getOutgoingSignedRequest(
		ISignatoryManager $signatoryManager,
		string $content,
		string $method,
		string $uri
	): IOutgoingSignedRequest {
		$signedRequest = new OutgoingSignedRequest($content);

		$parsed = parse_url($uri);
		$signedRequest->setHost($parsed['host'])
					  ->setAlgorithm($options['algorithm'] ?? 'sha256')
					  ->setSignatory($signatoryManager->getLocalSignatory());

		$options = $signatoryManager->getOptions();
		$this->setOutgoingSignatureHeader(
			$signedRequest,
			strtolower($method),
			$parsed['path'] ?? '/',
			$options['dateHeader'] ?? self::DATE_HEADER
		);
		$this->setOutgoingClearSignature($signedRequest);
		$this->setOutgoingSignedSignature($signedRequest);
		$this->signingOutgoingRequest($signedRequest);

		return $signedRequest;
	}

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
	 * confirm request is not older than ttl
	 *
	 * @param IIncomingSignedRequest $signedRequest
	 * @param int $ttl
	 *
	 * @throws IncomingRequestException
	 */
	private function verifyIncomingRequestTime(IIncomingSignedRequest $signedRequest, int $ttl): void {
		$request = $signedRequest->getRequest();
		$date = $request->getHeader('date');
		if ($date === '') {
			throw new IncomingRequestException('missing date in header');
		}

		try {
			$dTime = new \DateTime($request->getHeader('date'));
			$signedRequest->setTime($dTime->getTimestamp());
		} catch (\Exception $e) {
			$this->logger->warning(
				'datetime exception', ['exception' => $e, 'header' => $request->getHeader('date')]
			);
			throw new IncomingRequestException('datetime exception');
		}

		if ($signedRequest->getTime() < (time() - $ttl)) {
			throw new IncomingRequestException('object is too old');
		}
	}


	/**
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @throws IncomingRequestException
	 */
	private function verifyIncomingRequestContent(IIncomingSignedRequest $signedRequest): void {
		$request = $signedRequest->getRequest();
		$contentLength = $request->getHeader('content-length');
		if ($contentLength === '') {
			throw new IncomingRequestException('missing content-length in header');
		}

		if (strlen($signedRequest->getBody()) !== (int)$request->getHeader('content-length')) {
			throw new IncomingRequestException(
				'inexact content-length in header: ' . strlen($signedRequest->getBody()) . ' vs '
				. (int)$request->getHeader('content-length')
			);
		}

		$digest = $request->getHeader('digest');
		if ($digest === '' || $digest !== $signedRequest->getDigest()) {
			throw new IncomingRequestException('invalid value for digest in header');
		}
	}

	/**
	 * @param IIncomingSignedRequest $signedRequest
	 */
	private function prepIncomingSignatureHeader(IIncomingSignedRequest $signedRequest): void {
		$sign = [];
		$request = $signedRequest->getRequest();
		foreach (explode(',', $request->getHeader('Signature')) as $entry) {
			if ($entry === '' || !strpos($entry, '=')) {
				continue;
			}

			[$k, $v] = explode('=', $entry, 2);
			preg_match('/"([^"]+)"/', $v, $varr);
			if ($varr[0] !== null) {
				$v = trim($varr[0], '"');
			}
			$sign[$k] = $v;
		}

		$signedRequest->setSignatureHeader($sign);
	}


	/**
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @throws IncomingRequestException
	 * @throws InvalidKeyOriginException
	 */
	private function verifyIncomingSignatureHeader(IIncomingSignedRequest $signedRequest): void {
		$data = $signedRequest->getSignatureHeader();
		if (!array_key_exists('keyId', $data) || !array_key_exists('headers', $data)
			|| !array_key_exists('signature', $data)) {
			throw new IncomingRequestException('missing keys in signature headers: ' . json_encode($data));
		}

		$signedRequest->setOrigin($this->getKeyOrigin($data['keyId']));
		$signedRequest->setSignedSignature($data['signature']);
	}


	/**
	 * @param IIncomingSignedRequest $signedRequest
	 * @param array $extraSignatureHeaders
	 *
	 * @throws IncomingRequestException
	 */
	private function prepEstimatedSignature(
		IIncomingSignedRequest $signedRequest,
		array $extraSignatureHeaders = []
	): void {
		$request = $signedRequest->getRequest();
		$headers = explode(' ', $signedRequest->getSignatureHeader()['headers'] ?? []);

		$enforceHeaders = array_merge(
			['date', 'host', 'content-length', 'digest'],
			$extraSignatureHeaders
		);

		$missingHeaders = array_diff($enforceHeaders, $headers);
		if ($missingHeaders !== []) {
			throw new IncomingRequestException(
				'missing elements in headers: ' . json_encode($missingHeaders)
			);
		}

		$target = strtolower($request->getMethod()) . " " . $request->getRequestUri();
		$estimated = ['(request-target): ' . $target];

		foreach ($headers as $key) {
			$value = $request->getHeader($key);
			if (strtolower($key) === 'host') {
				$value = $request->getServerHost();
			}
			if ($value === '') {
				throw new IncomingRequestException('empty elements in header ' . $key);
			}

			$estimated[] = $key . ': ' . $value;
		}
		$signedRequest->setEstimatedSignature(implode("\n", $estimated));
	}


	/**
	 * @param IIncomingSignedRequest $signedRequest
	 * @param ISignatoryManager $signatoryManager
	 *
	 * @throws SignatoryNotFoundException
	 * @throws SignatureException
	 */
	private function verifyIncomingRequestSignature(
		IIncomingSignedRequest $signedRequest,
		ISignatoryManager $signatoryManager,
		int $ttlSignatory,
	): void {
		$knownSignatory = null;
		try {
			$knownSignatory = $this->getStoredSignatory($signedRequest->getKeyId());
			if ($ttlSignatory > 0 && $knownSignatory->getLastEdited() < (time() - $ttlSignatory)) {
				$signatory = $this->getSafeRemoteSignatory($signatoryManager, $signedRequest);
				$this->updateSignatoryMetadata($signatory);
				$knownSignatory->setMetadata($signatory->getMetadata());
			}

			$signedRequest->setSignatory($knownSignatory);
			$this->verifySignedRequest($signedRequest);
		} catch (InvalidKeyOriginException $e) {
			throw $e; // issue while requesting remote instance also means there is no 2nd try
		} catch (SignatoryNotFoundException|SignatureException) {
			try {
				$signatory = $this->getSafeRemoteSignatory($signatoryManager, $signedRequest);
			} catch (SignatoryNotFoundException $e) {
				$this->manageDeprecatedSignatory($knownSignatory);
				throw $e;
			}
			$signedRequest->setSignatory($signatory);
			$this->storeSignatory($signatory);
			$this->verifySignedRequest($signedRequest);
		}
	}


	/**
	 * @param ISignatoryManager $signatoryManager
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return ISignatory
	 * @throws InvalidKeyOriginException
	 * @throws SignatoryNotFoundException
	 */
	private function getSafeRemoteSignatory(
		ISignatoryManager $signatoryManager,
		IIncomingSignedRequest $signedRequest
	): ISignatory {
		$signatory = $signatoryManager->getRemoteSignatory($signedRequest);
		if ($signatory === null) {
			throw new SignatoryNotFoundException('empty result from getRemoteSignatory');
		}
		if ($signatory->getKeyId() !== $signedRequest->getKeyId()) {
			throw new InvalidKeyOriginException('keyId from signatory not related to the one from request');
		}

		return $signatory->setProviderId($signatoryManager->getProviderId());
	}

	private function setOutgoingSignatureHeader(
		IOutgoingSignedRequest $signedRequest,
		string $method,
		string $path,
		string $dateHeader
	): void {
		$header = [
			'(request-target)' => $method . ' ' . $path,
			'content-length' => strlen($signedRequest->getBody()),
			'date' => gmdate($dateHeader),
			'digest' => $signedRequest->getDigest(),
			'host' => $signedRequest->getHost()
		];

		$signedRequest->setSignatureHeader($header);
	}


	/**
	 * @param IOutgoingSignedRequest $signedRequest
	 */
	private function setOutgoingClearSignature(IOutgoingSignedRequest $signedRequest): void {
		$signing = [];
		$header = $signedRequest->getSignatureHeader();
		foreach (array_keys($header) as $element) {
			$value = $header[$element];
			$signing[] = $element . ': ' . $value;
			if ($element !== '(request-target)') {
				$signedRequest->addHeader($element, $value);
			}
		}

		$signedRequest->setClearSignature(implode("\n", $signing));
	}


	private function setOutgoingSignedSignature(IOutgoingSignedRequest $signedRequest): void {
		$clear = $signedRequest->getClearSignature();
		$signed = $this->signString($clear, $signedRequest->getSignatory(), $signedRequest->getAlgorithm());
		$signedRequest->setSignedSignature($signed);
	}

	private function signingOutgoingRequest(IOutgoingSignedRequest $signedRequest): void {
		$signatureHeader = $signedRequest->getSignatureHeader();
		$headers = array_diff(array_keys($signatureHeader), ['(request-target)']);
		$signatory = $signedRequest->getSignatory();
		$signatureElements = [
			'keyId="' . $signatory->getKeyId() . '"',
			'algorithm="' . $this->getChosenEncryption($signedRequest->getAlgorithm()) . '"',
			'headers="' . implode(' ', $headers) . '"',
			'signature="' . $signedRequest->getSignedSignature() . '"'
		];

		$signedRequest->addHeader('Signature', implode(',', $signatureElements));
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
				$signedRequest->getEstimatedSignature(),
				base64_decode($signedRequest->getSignedSignature()),
				$publicKey,
				$this->getUsedEncryption($signedRequest)
			);
		} catch (InvalidSignatureException $e) {
			$this->logger->debug('signature issue', ['signed' => $signedRequest, 'exception' => $e]);
			throw $e;
		}
	}


	private function getUsedEncryption(IIncomingSignedRequest $signedRequest): SignatureAlgorithm {
		$data = $signedRequest->getSignatureHeader();

		return match ($data['algorithm']) {
			'rsa-sha512' => SignatureAlgorithm::SHA512,
			default => SignatureAlgorithm::SHA256,
		};
	}

	private function getChosenEncryption(string $algorithm): string {
		return match ($algorithm) {
			'sha512' => 'ras-sha512',
			default => 'ras-sha256',
		};
	}

	public function getOpenSSLAlgo(string $algorithm): int {
		return match ($algorithm) {
			'sha512' => OPENSSL_ALGO_SHA512,
			default => OPENSSL_ALGO_SHA256,
		};
	}


	public function signString(string $clear, ISignatory $signatory, string $algorithm): string {
		$privateKey = $signatory->getPrivateKey();
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		openssl_sign($clear, $signed, $privateKey, $this->getOpenSSLAlgo($algorithm));

		return base64_encode($signed);
	}


	/**
	 * @param string $clear
	 * @param string $signed
	 * @param string $publicKey
	 * @param SignatureAlgorithm $algo
	 *
	 * @return void
	 * @throws InvalidSignatureException
	 */
	private function verifyString(
		string $clear,
		string $signed,
		string $publicKey,
		SignatureAlgorithm $algo = SignatureAlgorithm::SHA256
	): void {
		if (openssl_verify($clear, $signed, $publicKey, $algo->value) !== 1) {
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
		$qb->select('id', 'provider_id', 'host', 'account', 'key_id', 'key_id_sum', 'public_key', 'metadata', 'type', 'status', 'creation', 'last_updated');
		$qb->from(self::TABLE_SIGNATORIES);
		$qb->where($qb->expr()->eq('key_id_sum', $qb->createNamedParameter($this->hashKeyId($keyId))));

		$result = $qb->executeQuery();
		$row = $result->fetchOne();
		$result->closeCursor();
		if (!$row) {
			throw new SignatoryNotFoundException();
		}

		$signature = new Signatory($row['key_id'], $row['public_key']);
		$signature->importFromDatabase($row);

		return $signature;
	}

	/**
	 * @param ISignatory $signatory
	 *
	 * @throws DBException
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

	private function insertSignatory(ISignatory $signatory): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert(self::TABLE_SIGNATORIES)
		   ->setValue('provider_id', $qb->createNamedParameter($signatory->getProviderId()))
		   ->setValue('host', $qb->createNamedParameter(parse_url($signatory->getKeyId(), PHP_URL_HOST)))
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
				break;

			case SignatoryType::STATIC:
				// TODO: send warning to admin
				throw new SignatoryConflictException();
				break;
		}
	}

	/**
	 * This is called when a remote signatory does not exist anymore
	 *
	 * @param ISignatory|null $knownSignatory NULL is not known
	 * @throws SignatoryConflictException
	 * @throws SignatoryNotFoundException
	 */
	private function manageDeprecatedSignatory(?ISignatory $knownSignatory): void {
		switch($knownSignatory?->getType()) {
			case null: // unknown in local database
			case SignatoryType::FORGIVABLE: // who cares ?
				throw new SignatoryNotFoundException(); // meaning we just return the correct exception

			case SignatoryType::REFRESHABLE:
				// TODO: send notice to admin
				throw new SignatoryConflictException();

			case SignatoryType::TRUSTED:
			case SignatoryType::STATIC:
				// TODO: send warning to admin
				throw new SignatoryConflictException();
		}
	}


	private function updateSignatoryPublicKey(ISignatory $signatory): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE_SIGNATORIES)
		   ->set('signatory', $qb->createNamedParameter($signatory->getPublicKey()))
		   ->set('last_updated', $qb->createNamedParameter(time()));

		$qb->where($qb->expr()->eq('key_id_prim', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId()))));
		$qb->executeStatement();
	}

	private function updateSignatoryMetadata(ISignatory $signatory): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE_SIGNATORIES)
		   ->set('metadata', $qb->createNamedParameter(json_encode($signatory->getMetadata())))
		   ->set('last_updated', $qb->createNamedParameter(time()));

		$qb->where($qb->expr()->eq('key_id_prim', $qb->createNamedParameter($this->hashKeyId($signatory->getKeyId()))));
		$qb->executeStatement();
	}

	private function deleteSignatory(string $keyId): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete(self::TABLE_SIGNATORIES)
			->where($qb->expr()->eq('key_id_prim', $qb->createNamedParameter($this->hashKeyId($keyId))));
		$qb->executeStatement();
	}


	/**
	 * @param string $keyId
	 *
	 * @return string
	 * @throws InvalidKeyOriginException
	 */
	private function getKeyOrigin(string $keyId): string {
		$host = parse_url($keyId, PHP_URL_HOST);
		if (is_string($host) && $host !== '') {
			return $host;
		}

		throw new InvalidKeyOriginException('cannot retrieve origin from ' . $keyId);
	}

	private function hashKeyId(string $keyId): string {
		return hash('sha256', $keyId);
	}
}
