<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

/**
 * Shared Key authorization for the Azure Blob service.
 *
 * @see https://learn.microsoft.com/rest/api/storageservices/authorize-with-shared-key
 */
class AzureSharedKeySigner {
	/** Standard headers in the order they appear in the string-to-sign */
	private const SIGNED_HEADERS = [
		'content-encoding',
		'content-language',
		'content-length',
		'content-md5',
		'content-type',
		'date',
		'if-modified-since',
		'if-match',
		'if-none-match',
		'if-unmodified-since',
		'range',
	];

	private string $key;

	public function __construct(
		private string $accountName,
		#[\SensitiveParameter]
		string $accountKey,
	) {
		$key = base64_decode($accountKey, true);
		if ($key === false || $key === '') {
			throw new \InvalidArgumentException('The Azure account key is not valid base64');
		}
		$this->key = $key;
	}

	public function getAccountName(): string {
		return $this->accountName;
	}

	/**
	 * @param string $path the URL-encoded request path, as sent
	 * @param array<string, string> $query the decoded query parameters
	 * @param array<string, string> $headers the request headers
	 */
	public function getAuthorizationHeader(string $method, string $path, array $query, array $headers): string {
		return 'SharedKey ' . $this->accountName . ':' . $this->sign($this->getStringToSign($method, $path, $query, $headers));
	}

	/**
	 * @param array<string, string> $query
	 * @param array<string, string> $headers
	 */
	public function getStringToSign(string $method, string $path, array $query, array $headers): string {
		$headers = array_change_key_case($headers, CASE_LOWER);

		$lines = [strtoupper($method)];
		foreach (self::SIGNED_HEADERS as $name) {
			$value = (string)($headers[$name] ?? '');
			// Since version 2015-02-21 a zero length is signed as an empty string
			$lines[] = ($name === 'content-length' && $value === '0') ? '' : $value;
		}

		$msHeaders = array_filter($headers, static fn (string $name): bool => str_starts_with($name, 'x-ms-'), ARRAY_FILTER_USE_KEY);
		ksort($msHeaders, SORT_STRING);
		foreach ($msHeaders as $name => $value) {
			$lines[] = $name . ':' . trim(preg_replace('/\s+/', ' ', (string)$value) ?? '');
		}

		$resource = '/' . $this->accountName . $path;
		$query = array_change_key_case($query, CASE_LOWER);
		ksort($query, SORT_STRING);
		foreach ($query as $name => $value) {
			$resource .= "\n" . $name . ':' . $value;
		}
		$lines[] = $resource;

		return implode("\n", $lines);
	}

	/**
	 * @return string the base64 encoded HMAC-SHA256 of the value with the account key
	 */
	public function sign(string $value): string {
		return base64_encode(hash_hmac('sha256', $value, $this->key, true));
	}
}
