<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\Exceptions\IncomingRequestException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use NCU\Security\Signature\Exceptions\SignatureNotFoundException;
use NCU\Security\Signature\Model\Signatory;

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
 *     "Signature": "keyId=\"https://author.hostname/key\",algorithm=\"sha256\",headers=\"content-length date digest host\",signature=\"DzN12OCS1rsA[...]o0VmxjQooRo6HHabg==\""
 * }
 *
 * 'content-length' is the total length of the data/content
 * 'date' is the datetime the request have been initiated
 * 'digest' is a checksum of the data/content
 * 'host' is the hostname of the recipient of the request (remote when signing outgoing request, local on incoming request)
 * 'Signature' contains the signature generated using the private key, and metadata:
 *    - 'keyId' is a unique id, formatted as an url. hostname is used to retrieve the public key via custom discovery
 *    - 'algorithm' define the algorithm used to generate signature
 *    - 'headers' contains a list of element used during the generation of the signature
 *    - 'signature' is the encrypted string, using local private key, of an array containing elements
 *      listed in 'headers' and their value. Some elements (content-length date digest host) are mandatory
 *      to ensure authenticity override protection.
 *
 * This interface can be used to inject {@see SignatureManager} in your code:
 *
 * ```php
 *    public function __construct(
 *        private ISignatureManager $signatureManager,
 *    ) {}
 *  ```
 *
 *   instead obtained from {@see ISignatureManager::getIncomingSignedRequest}.
 *
 * @experimental 31.0.0
 */
interface ISignatureManager {
	/**
	 * Extracting data from headers and body from the incoming request.
	 * Compare headers and body to confirm authenticity of remote instance.
	 * Returns details about the signed request or throws exception.
	 *
	 * Should be called from Controller.
	 *
	 * @param ISignatoryManager $signatoryManager used to get details about remote instance
	 * @param string|null $body if NULL, body will be extracted from php://input
	 *
	 * @return IIncomingSignedRequest
	 * @throws IncomingRequestException if anything looks wrong with the incoming request
	 * @throws SignatureNotFoundException if incoming request is not signed
	 * @throws SignatureException if signature could not be confirmed
	 * @experimental 31.0.0
	 */
	public function getIncomingSignedRequest(ISignatoryManager $signatoryManager, ?string $body = null): IIncomingSignedRequest;

	/**
	 * Preparing signature (and headers) to sign an outgoing request.
	 * Returns a IOutgoingSignedRequest containing all details to finalise the packaging of the whole payload
	 *
	 * @param ISignatoryManager $signatoryManager
	 * @param string $content body to be signed
	 * @param string $method needed in the signature
	 * @param string $uri needed in the signature
	 *
	 * @return IOutgoingSignedRequest
	 * @experimental 31.0.0
	 */
	public function getOutgoingSignedRequest(ISignatoryManager $signatoryManager, string $content, string $method, string $uri): IOutgoingSignedRequest;

	/**
	 * Complete the full process of signing and filling headers from payload when generating
	 * an outgoing request with IClient
	 *
	 * @param ISignatoryManager $signatoryManager
	 * @param array $payload original payload, will be used to sign and completed with new headers with signature elements
	 * @param string $method needed in the signature
	 * @param string $uri needed in the signature
	 *
	 * @return array new payload to be sent, including original payload and signature elements in headers
	 * @experimental 31.0.0
	 */
	public function signOutgoingRequestIClientPayload(ISignatoryManager $signatoryManager, array $payload, string $method, string $uri): array;

	/**
	 * returns remote signatory stored in local database, based on the remote host.
	 *
	 * @param string $host remote host
	 * @param string $account linked account, should be used when multiple signature can exist for the same host
	 *
	 * @return Signatory
	 * @throws SignatoryNotFoundException if entry does not exist in local database
	 * @experimental 31.0.0
	 */
	public function getSignatory(string $host, string $account = ''): Signatory;

	/**
	 * returns a fully formatted keyId, based on a fix hostname and path
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws IdentityNotFoundException if hostname is not set
	 * @experimental 31.0.0
	 */
	public function generateKeyIdFromConfig(string $path): string;

	/**
	 * returns hostname:port extracted from an uri
	 *
	 * @param string $uri
	 *
	 * @return string
	 * @throws IdentityNotFoundException if identity cannot be extracted
	 * @experimental 31.0.0
	 */
	public function extractIdentityFromUri(string $uri): string;
}
