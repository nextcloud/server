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
use OCP\IRequest;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\SignatoryException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatoryManager;

/**
 * RFC 9421 signed response profile used by OCM.
 *
 * The response is bound to the triggering request through the `;req`
 * components: method, target URI, and the request's `ocm` entries of the
 * Signature-Input and Signature dictionaries. The `;key="ocm"` parameter
 * selects only that dictionary entry, so additional signatures on the same
 * message (e.g. added by a proxy) do not break verification at the requester.
 *
 * The signature is computed lazily when the response is read out
 * ({@see getStatus()}, {@see getHeaders()}, {@see render()}) and recomputed
 * when the status or headers change afterwards, so the signature always
 * matches the response that is actually sent.
 *
 * @template S of Http::STATUS_*
 * @template-extends Response<S, array<string, mixed>>
 */
final class Rfc9421OutgoingSignedResponse extends Response {
	private const COMPONENTS = [
		'@status',
		'content-type',
		'content-digest',
		'@method;req',
		'@target-uri;req',
		'signature-input;key="ocm";req',
		'signature;key="ocm";req',
	];

	private bool $signed = false;
	private bool $signing = false;
	private string $signatureBaseString = '';

	/**
	 * @param S $status response status code, covered by the signature
	 * @param string $contentType value of the signed Content-Type header
	 * @throws IncomingRequestException if the request is not an RFC 9421 signed request
	 * @throws SignatoryException if no local private key is available
	 */
	public function __construct(
		private readonly string $body,
		private readonly IIncomingSignedRequest $incomingRequest,
		private readonly ISignatoryManager $signatoryManager,
		int $status = Http::STATUS_OK,
		string $contentType = 'application/json; charset=utf-8',
	) {
		$options = $signatoryManager->getOptions();
		$digestAlgorithm = (string)($options['rfc9421.contentDigestAlgorithm'] ?? ContentDigest::ALGO_SHA256);
		parent::__construct($status, [
			'Content-Type' => $contentType,
			'Content-Digest' => ContentDigest::compute($body, $digestAlgorithm),
		]);

		$request = $incomingRequest->getRequest();
		if ($request->getHeader('Signature-Input') === '' || $request->getHeader('Signature') === '') {
			throw new IncomingRequestException('RFC 9421 signed request required');
		}
		if ($signatoryManager->getLocalSignatory()->getPrivateKey() === '') {
			throw new SignatoryException('empty private key');
		}
	}

	#[\Override]
	public function setStatus($status): static {
		$this->signed = false;
		return parent::setStatus($status);
	}

	#[\Override]
	public function addHeader($name, $value) {
		if (!$this->signing) {
			$this->signed = false;
		}
		return parent::addHeader($name, $value);
	}

	#[\Override]
	public function setHeaders(array $headers): static {
		$this->signed = false;
		return parent::setHeaders($headers);
	}

	#[\Override]
	public function getStatus() {
		$this->sign();
		return parent::getStatus();
	}

	#[\Override]
	public function getHeaders() {
		$this->sign();
		return parent::getHeaders();
	}

	#[\Override]
	public function render(): string {
		$this->sign();
		return $this->body;
	}

	public function getSignatureBaseString(): string {
		$this->sign();
		return $this->signatureBaseString;
	}

	/**
	 * Compute and attach the signature headers from the current status and
	 * headers. Runs at most once between mutations of the response.
	 */
	private function sign(): void {
		if ($this->signed || $this->signing) {
			return;
		}
		$this->signing = true;
		try {
			$options = $this->signatoryManager->getOptions();
			$signatory = $this->signatoryManager->getLocalSignatory();
			$signingAlgorithm = SignatureBase::resolveSigningAlgorithm($options);
			$paramsLine = SignatureBase::serializeSignatureParams(
				self::COMPONENTS,
				SignatureBase::buildSignatureParams($options, $signatory->getKeyId(), $signingAlgorithm),
			);

			$request = $this->incomingRequest->getRequest();
			$this->signatureBaseString = SignatureBase::buildResponse(
				status: parent::getStatus(),
				responseHeaders: $this->responseHeadersByLowercaseName(),
				requestMethod: $request->getMethod(),
				requestUri: SignatureBase::reconstructTargetUri($request),
				requestHeaders: $this->collectRequestHeaders($request),
				components: self::COMPONENTS,
				signatureParamsLine: $paramsLine,
			);

			$rawSignature = Algorithm::sign(
				$this->signatureBaseString,
				$signatory->getPrivateKey(),
				$signingAlgorithm,
			);
			[$signatureInput, $signature] = SignatureBase::formatSignatureHeaders(
				SignatureBase::OCM_SIGNATURE_LABEL,
				$paramsLine,
				$rawSignature,
			);
			parent::addHeader('Signature-Input', $signatureInput);
			parent::addHeader('Signature', $signature);
			$this->signed = true;
		} finally {
			$this->signing = false;
		}
	}

	/**
	 * @return array<lowercase-string, string>
	 */
	private function responseHeadersByLowercaseName(): array {
		$out = [];
		foreach (parent::getHeaders() as $name => $value) {
			$out[strtolower($name)] = (string)$value;
		}
		return $out;
	}

	/**
	 * Plain request fields covered by `;req` components, keyed by lowercase name.
	 *
	 * @return array<lowercase-string, string>
	 * @throws SignatureException when a covered request field is missing
	 */
	private function collectRequestHeaders(IRequest $request): array {
		$out = [];
		foreach (self::COMPONENTS as $component) {
			[$name, $req] = SignatureBase::parseComponentIdentifier($component);
			if (!$req || str_starts_with($name, '@')) {
				continue;
			}
			$value = $request->getHeader($name);
			if ($value === '') {
				throw new SignatureException('missing request field for signature: ' . $name);
			}
			$out[strtolower($name)] = $value;
		}
		return $out;
	}
}
