<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Model;

use OC\Security\Signature\Rfc9421\Algorithm;
use OC\Security\Signature\Rfc9421\ContentDigest;
use OC\Security\Signature\Rfc9421\SignatureBase;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\SignatoryException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatoryManager;

/**
 * RFC 9421 signed response profile used by OCM.
 *
 * @template-extends Response<Http::STATUS_OK, array<string, mixed>>
 */
final class Rfc9421OutgoingSignedResponse extends Response {
	private const COMPONENTS = [
		'@status',
		'content-type',
		'content-digest',
		'@method;req',
		'@target-uri;req',
		'signature-input;req',
	];

	private string $signatureBaseString;

	public function __construct(
		private readonly string $body,
		IIncomingSignedRequest $incomingRequest,
		ISignatoryManager $signatoryManager,
	) {
		$options = $signatoryManager->getOptions();
		$responseHeaders = [
			'content-type' => 'application/json; charset=utf-8',
			'content-digest' => ContentDigest::compute(
				$body,
				(string)($options['rfc9421.contentDigestAlgorithm'] ?? ContentDigest::ALGO_SHA256),
			),
		];
		parent::__construct(Http::STATUS_OK, [
			'Content-Type' => $responseHeaders['content-type'],
			'Content-Digest' => $responseHeaders['content-digest'],
		]);

		$request = $incomingRequest->getRequest();
		$requestSignatureInput = $request->getHeader('Signature-Input');
		if ($requestSignatureInput === '') {
			throw new IncomingRequestException('RFC 9421 signed request required');
		}

		$signatory = $signatoryManager->getLocalSignatory();
		$signingAlgorithm = (string)($options['rfc9421.signingAlgorithm'] ?? 'ecdsa-p256-sha256');
		$signatureParams = [
			'created' => time(),
			'keyid' => $signatory->getKeyId(),
			'tag' => 'ocm',
		];
		if ((bool)($options['rfc9421.includeAlgParameter'] ?? false)) {
			$signatureParams['alg'] = $signingAlgorithm;
		}

		$signatureParamsLine = SignatureBase::serializeSignatureParams(
			self::COMPONENTS,
			$signatureParams,
		);
		$this->signatureBaseString = SignatureBase::buildResponse(
			status: Http::STATUS_OK,
			responseHeaders: $responseHeaders,
			requestMethod: $request->getMethod(),
			requestUri: $request->getServerProtocol() . '://' . $request->getServerHost() . $request->getRequestUri(),
			requestHeaders: ['signature-input' => $requestSignatureInput],
			components: self::COMPONENTS,
			signatureParamsLine: $signatureParamsLine,
		);

		$privateKey = $signatory->getPrivateKey();
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		$rawSignature = Algorithm::sign(
			$this->signatureBaseString,
			$privateKey,
			$signingAlgorithm,
		);
		$this->addHeader('Signature-Input', 'ocm=' . $signatureParamsLine);
		$this->addHeader('Signature', 'ocm=:' . base64_encode($rawSignature) . ':');
	}

	public function getSignatureBaseString(): string {
		return $this->signatureBaseString;
	}

	#[\Override]
	public function render(): string {
		return $this->body;
	}
}
