<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Model;

use Firebase\JWT\Key;
use OC\Security\Signature\Rfc9421\Algorithm;
use OC\Security\Signature\Rfc9421\ContentDigest;
use OC\Security\Signature\Rfc9421\SignatureBase;
use OCP\Http\Client\IResponse;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\InvalidSignatureException;
use OCP\Security\Signature\Exceptions\SignatureException;

final class Rfc9421IncomingSignedResponse {
	private const COMPONENTS = [
		'@status',
		'content-type',
		'content-digest',
		'@method;req',
		'@target-uri;req',
		'signature-input;key="ocm";req',
		'signature;key="ocm";req',
	];
	/** @var array<string, scalar> */
	private array $signatureParams;
	private string $signatureBaseString;
	private string $rawSignature;

	public function __construct(
		IResponse $response,
		string $requestMethod,
		string $requestUri,
		string $requestSignatureInput,
		string $requestSignature,
		array $options = [],
	) {
		if ($requestSignatureInput === '') {
			throw new IncomingRequestException(
				'request Signature-Input is required',
			);
		}
		if ($requestSignature === '') {
			throw new IncomingRequestException(
				'request Signature is required',
			);
		}

		$body = $response->getBody();
		if (!is_string($body)) {
			throw new IncomingRequestException(
				'signed response body is unavailable',
			);
		}

		$signature = Rfc9421IncomingSignedRequest::parseOcmSignature(
			$response->getHeader('Signature-Input'),
			$response->getHeader('Signature'),
		);
		$components = $signature['components'];
		$missing = array_diff(self::COMPONENTS, $components);
		$unsupported = array_diff($components, self::COMPONENTS);
		if ($missing !== [] || $unsupported !== []) {
			throw new IncomingRequestException(
				'invalid signed response components',
			);
		}

		$this->signatureParams = $signature['params'];
		$this->rawSignature = $signature['rawSignature'];
		Rfc9421IncomingSignedRequest::verifyTimestamps(
			$this->signatureParams,
			$options,
		);

		$contentType = $response->getHeader('Content-Type');
		$contentDigest = $response->getHeader('Content-Digest');
		if ($contentType === '' || $contentDigest === '') {
			throw new IncomingRequestException(
				'signed response headers are missing',
			);
		}
		if (!ContentDigest::verify($contentDigest, $body)) {
			throw new IncomingRequestException(
				'content-digest does not match response body',
			);
		}

		Rfc9421IncomingSignedRequest::requireKeyId(
			$this->signatureParams,
		);

		$this->signatureBaseString = SignatureBase::buildResponse(
			status: $response->getStatusCode(),
			responseHeaders: [
				'content-type' => $contentType,
				'content-digest' => $contentDigest,
			],
			requestMethod: $requestMethod,
			requestUri: $requestUri,
			requestHeaders: [
				'signature-input' => $requestSignatureInput,
				'signature' => $requestSignature,
			],
			components: $components,
			signatureParamsLine:
				SignatureBase::serializeSignatureParams(
					$components,
					$this->signatureParams,
				),
		);
	}

	public function getKeyId(): string {
		return Rfc9421IncomingSignedRequest::requireKeyId(
			$this->signatureParams,
		);
	}

	public function getSignatureBaseString(): string {
		return $this->signatureBaseString;
	}

	public function verify(Key $key): void {
		try {
			$verified = Algorithm::verify(
				$this->signatureBaseString,
				$this->rawSignature,
				$key,
				isset($this->signatureParams['alg'])
					? (string)$this->signatureParams['alg']
					: null,
			);
		} catch (SignatureException $e) {
			throw new InvalidSignatureException(
				$e->getMessage(),
				0,
				$e,
			);
		}
		if (!$verified) {
			throw new InvalidSignatureException(
				'signature verification failed',
			);
		}
	}

}
