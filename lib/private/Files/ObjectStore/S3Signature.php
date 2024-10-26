<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use Aws\Credentials\CredentialsInterface;
use Aws\S3\S3Client;
use Aws\S3\S3UriParser;
use Aws\Signature\SignatureInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Legacy Amazon S3 signature implementation
 */
class S3Signature implements SignatureInterface {
	/** @var array Query string values that must be signed */
	private $signableQueryString = [
		'acl', 'cors', 'delete', 'lifecycle', 'location', 'logging',
		'notification', 'partNumber', 'policy', 'requestPayment',
		'response-cache-control', 'response-content-disposition',
		'response-content-encoding', 'response-content-language',
		'response-content-type', 'response-expires', 'restore', 'tagging',
		'torrent', 'uploadId', 'uploads', 'versionId', 'versioning',
		'versions', 'website'
	];

	/** @var array Sorted headers that must be signed */
	private $signableHeaders = ['Content-MD5', 'Content-Type'];

	/** @var \Aws\S3\S3UriParser S3 URI parser */
	private $parser;

	public function __construct() {
		$this->parser = new S3UriParser();
		// Ensure that the signable query string parameters are sorted
		sort($this->signableQueryString);
	}

	public function signRequest(
		RequestInterface $request,
		CredentialsInterface $credentials,
	) {
		$request = $this->prepareRequest($request, $credentials);
		$stringToSign = $this->createCanonicalizedString($request);
		$auth = 'AWS '
			. $credentials->getAccessKeyId() . ':'
			. $this->signString($stringToSign, $credentials);

		return $request->withHeader('Authorization', $auth);
	}

	public function presign(
		RequestInterface $request,
		CredentialsInterface $credentials,
		$expires,
		array $options = [],
	) {
		$query = [];
		// URL encoding already occurs in the URI template expansion. Undo that
		// and encode using the same encoding as GET object, PUT object, etc.
		$uri = $request->getUri();
		$path = S3Client::encodeKey(rawurldecode($uri->getPath()));
		$request = $request->withUri($uri->withPath($path));

		// Make sure to handle temporary credentials
		if ($token = $credentials->getSecurityToken()) {
			$request = $request->withHeader('X-Amz-Security-Token', $token);
			$query['X-Amz-Security-Token'] = $token;
		}

		if ($expires instanceof \DateTime) {
			$expires = $expires->getTimestamp();
		} elseif (!is_numeric($expires)) {
			$expires = strtotime($expires);
		}

		// Set query params required for pre-signed URLs
		$query['AWSAccessKeyId'] = $credentials->getAccessKeyId();
		$query['Expires'] = $expires;
		$query['Signature'] = $this->signString(
			$this->createCanonicalizedString($request, $expires),
			$credentials
		);

		// Move X-Amz-* headers to the query string
		foreach ($request->getHeaders() as $name => $header) {
			$name = strtolower($name);
			if (str_starts_with($name, 'x-amz-')) {
				$query[$name] = implode(',', $header);
			}
		}

		$queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

		return $request->withUri($request->getUri()->withQuery($queryString));
	}

	/**
	 * @param RequestInterface $request
	 * @param CredentialsInterface $creds
	 *
	 * @return RequestInterface
	 */
	private function prepareRequest(
		RequestInterface $request,
		CredentialsInterface $creds,
	) {
		$modify = [
			'remove_headers' => ['X-Amz-Date'],
			'set_headers' => ['Date' => gmdate(\DateTimeInterface::RFC2822)]
		];

		// Add the security token header if one is being used by the credentials
		if ($token = $creds->getSecurityToken()) {
			$modify['set_headers']['X-Amz-Security-Token'] = $token;
		}

		return Psr7\Utils::modifyRequest($request, $modify);
	}

	private function signString($string, CredentialsInterface $credentials) {
		return base64_encode(
			hash_hmac('sha1', $string, $credentials->getSecretKey(), true)
		);
	}

	private function createCanonicalizedString(
		RequestInterface $request,
		$expires = null,
	) {
		$buffer = $request->getMethod() . "\n";

		// Add the interesting headers
		foreach ($this->signableHeaders as $header) {
			$buffer .= $request->getHeaderLine($header) . "\n";
		}

		$date = $expires ?: $request->getHeaderLine('date');
		$buffer .= "{$date}\n"
			. $this->createCanonicalizedAmzHeaders($request)
			. $this->createCanonicalizedResource($request);

		return $buffer;
	}

	private function createCanonicalizedAmzHeaders(RequestInterface $request) {
		$headers = [];
		foreach ($request->getHeaders() as $name => $header) {
			$name = strtolower($name);
			if (str_starts_with($name, 'x-amz-')) {
				$value = implode(',', $header);
				if (strlen($value) > 0) {
					$headers[$name] = $name . ':' . $value;
				}
			}
		}

		if (!$headers) {
			return '';
		}

		ksort($headers);

		return implode("\n", $headers) . "\n";
	}

	private function createCanonicalizedResource(RequestInterface $request) {
		$data = $this->parser->parse($request->getUri());
		$buffer = '/';

		if ($data['bucket']) {
			$buffer .= $data['bucket'];
			if (!empty($data['key']) || !$data['path_style']) {
				$buffer .= '/' . $data['key'];
			}
		}

		// Add sub resource parameters if present.
		$query = $request->getUri()->getQuery();

		if ($query) {
			$params = Psr7\Query::parse($query);
			$first = true;
			foreach ($this->signableQueryString as $key) {
				if (array_key_exists($key, $params)) {
					$value = $params[$key];
					$buffer .= $first ? '?' : '&';
					$first = false;
					$buffer .= $key;
					// Don't add values for empty sub-resources
					if (strlen($value)) {
						$buffer .= "={$value}";
					}
				}
			}
		}

		return $buffer;
	}
}
