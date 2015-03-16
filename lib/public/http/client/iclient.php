<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Http\Client;

/**
 * Interface IClient
 *
 * @package OCP\Http
 */
interface IClient {

	/**
	 * Sends a GET request
	 * @param string $uri
	 * @param array $options Array such as
	 *              'query' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function get($uri, array $options = []);

	/**
	 * Sends a HEAD request
	 * @param string $uri
	 * @param array $options Array such as
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 * @return IResponse
	 */
	public function head($uri, $options = []);

	/**
	 * Sends a POST request
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 * @return IResponse
	 */
	public function post($uri, array $options = []);

	/**
	 * Sends a PUT request
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 * @return IResponse
	 */
	public function put($uri, array $options = []);

	/**
	 * Sends a DELETE request
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 * @return IResponse
	 */
	public function delete($uri, array $options = []);

	/**
	 * Sends a options request
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 * @return IResponse
	 */
	public function options($uri, array $options = []);
}
