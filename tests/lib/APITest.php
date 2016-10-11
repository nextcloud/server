<?php
/**
 * Copyright (c) 2013 Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class APITest extends \Test\TestCase {

	// Helps build a response variable

	/**
	 * @param string $message
	 */
	function buildResponse($shipped, $data, $code, $message=null) {
		$resp = new \OC_OCS_Result($data, $code, $message);
		$resp->addHeader('KEY', 'VALUE');
		return [
			'shipped' => $shipped,
			'response' => $resp,
			'app' => $this->getUniqueID('testapp_'),
		];
	}

	// Validate details of the result

	/**
	 * @param \OC_OCS_Result $result
	 */
	function checkResult($result, $success) {
		// Check response is of correct type
		$this->assertInstanceOf('OC_OCS_Result', $result);
		// Check if it succeeded
		/** @var $result \OC_OCS_Result */
		$this->assertEquals($success, $result->succeeded());
	}

	/**
	 * @return array
	 */
	public function versionDataScriptNameProvider() {
		return [
			// Valid script name
			[
				'/master/ocs/v2.php',
				true,
			],

			// Invalid script names
			[
				'/master/ocs/v2.php/someInvalidPathName',
				false,
			],
			[
				'/master/ocs/v1.php',
				false,
			],
			[
				'',
				false,
			],
		];
	}

	/**
	 * @dataProvider versionDataScriptNameProvider
	 * @param string $scriptName
	 * @param bool $expected
	 */
	public function testIsV2($scriptName, $expected) {
		$request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$request
			->expects($this->once())
			->method('getScriptName')
			->will($this->returnValue($scriptName));

		$this->assertEquals($expected, $this->invokePrivate(new \OC_API, 'isV2', [$request]));
	}

	function dataProviderTestOneResult() {
		return [
			[100, true],
			[101, false],
			[997, false],
		];
	}

	/**
	 * @dataProvider dataProviderTestOneResult
	 *
	 * @param $statusCode
	 * @param $succeeded
	 */
	public function testOneResult($statusCode, $succeeded) {
		// Setup some data arrays
		$data1 = [
			'users' => [
				'tom' => [
					'key' => 'value',
				],
				'frank' => [
					'key' => 'value',
				],
			]];

		// Test merging one success result
		$response = $this->buildResponse(true, $data1, $statusCode);
		$result = \OC_API::mergeResponses([$response]);
		$this->assertEquals($response['response'], $result);
		$this->checkResult($result, $succeeded);
	}

	function dataProviderTestMergeResponses() {
		return [
			// Two shipped success results
			[true, 100, true, 100, true],
			// Two shipped results, one success and one failure
			[true, 100, true, 998, false],
			// Two shipped results, both failure
			[true, 997, true, 998, false],
			// Two third party success results
			[false, 100, false, 100, true],
			// Two third party results, one success and one failure
			[false, 100, false, 998, false],
			// Two third party results, both failure
			[false, 997, false, 998, false],
			// One of each, both success
			[false, 100, true, 100, true],
			[true, 100, false, 100, true],
			// One of each, both failure
			[false, 997, true, 998, false],
			// One of each, shipped success
			[false, 997, true, 100, true],
			// One of each, third party success
			[false, 100, true, 998, false],
		];
	}
	/**
	 * @dataProvider dataProviderTestMergeResponses
	 *
	 * Test the merging of multiple responses
	 * @param $statusCode1
	 * @param $statusCode2
	 * @param $succeeded
	 */
	public function testMultipleMergeResponses($shipped1, $statusCode1, $shipped2, $statusCode2, $succeeded){
		// Tests that app responses are merged correctly
		// Setup some data arrays
		$data1 = array(
			'users' => array(
				'tom' => array(
					'key' => 'value',
				),
				'frank' => array(
					'key' => 'value',
				),
			));

		$data2 = array(
			'users' => array(
				'tom' => array(
					'key' => 'newvalue',
				),
				'jan' => array(
					'key' => 'value',
				),
			));

		// Two shipped success results
		$result = \OC_API::mergeResponses(array(
			$this->buildResponse($shipped1, $data1, $statusCode1, "message1"),
			$this->buildResponse($shipped2, $data2, $statusCode2, "message2"),
		));
		$this->checkResult($result, $succeeded);
		$resultData = $result->getData();
		$resultMeta = $result->getMeta();
		$resultHeaders = $result->getHeaders();
		$resultStatusCode = $result->getStatusCode();

		$this->assertArrayHasKey('jan', $resultData['users']);
		$this->assertArrayHasKey('KEY', $resultHeaders);

		// check if the returned status message matches the selected status code
		if ($resultStatusCode === 997) {
			$this->assertEquals('message1', $resultMeta['message']);
		} elseif ($resultStatusCode === 998) {
			$this->assertEquals('message2', $resultMeta['message']);
		} elseif ($resultStatusCode === 100) {
			$this->assertEquals(null, $resultMeta['message']);
		}

	}

}
