<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Http\Client;

/**
 * Interface IClient
 *
 * @since 8.1.0
 */
interface IClient {

	/**
	 * Default request timeout for requests
	 *
	 * @since 31.0.0
	 */
	public const DEFAULT_REQUEST_TIMEOUT = 30;

	/**
	 * Sends a GET request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'query' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 8.1.0
	 */
	public function get(string $uri, array $options = []): IResponse;

	/**
	 * Sends a HEAD request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 8.1.0
	 */
	public function head(string $uri, array $options = []): IResponse;

	/**
	 * Sends a POST request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 8.1.0
	 */
	public function post(string $uri, array $options = []): IResponse;

	/**
	 * Sends a PUT request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 8.1.0
	 */
	public function put(string $uri, array $options = []): IResponse;

	/**
	 * Sends a PATCH request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 29.0.0
	 */
	public function patch(string $uri, array $options = []): IResponse;

	/**
	 * Sends a DELETE request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 8.1.0
	 */
	public function delete(string $uri, array $options = []): IResponse;

	/**
	 * Sends an OPTIONS request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 8.1.0
	 */
	public function options(string $uri, array $options = []): IResponse;

	/**
	 * Get the response of a Throwable thrown by the request methods when possible
	 *
	 * @param \Throwable $e
	 * @return IResponse
	 * @throws \Throwable When $e did not have a response
	 * @since 29.0.0
	 */
	public function getResponseFromThrowable(\Throwable $e): IResponse;

	/**
	 * Sends a HTTP request
	 * @param string $method The HTTP method to use
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'query' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 * @since 29.0.0
	 */
	public function request(string $method, string $uri, array $options = []): IResponse;

	/**
	 * Sends an asynchronous GET request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'query' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IPromise
	 * @since 28.0.0
	 */
	public function getAsync(string $uri, array $options = []): IPromise;

	/**
	 * Sends an asynchronous HEAD request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IPromise
	 * @since 28.0.0
	 */
	public function headAsync(string $uri, array $options = []): IPromise;

	/**
	 * Sends an asynchronous POST request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IPromise
	 * @since 28.0.0
	 */
	public function postAsync(string $uri, array $options = []): IPromise;

	/**
	 * Sends an asynchronous PUT request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IPromise
	 * @since 28.0.0
	 */
	public function putAsync(string $uri, array $options = []): IPromise;

	/**
	 * Sends an asynchronous DELETE request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IPromise
	 * @since 28.0.0
	 */
	public function deleteAsync(string $uri, array $options = []): IPromise;

	/**
	 * Sends an asynchronous OPTIONS request
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 * @return IPromise
	 * @since 28.0.0
	 */
	public function optionsAsync(string $uri, array $options = []): IPromise;
}
