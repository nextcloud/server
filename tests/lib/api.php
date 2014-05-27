<?php
/**
 * Copyright (c) 2013 Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_API extends PHPUnit_Framework_TestCase {

	// Helps build a response variable
	function buildResponse($shipped, $data, $code, $message=null) {
		return array(
			'shipped' => $shipped,
			'response' => new OC_OCS_Result($data, $code, $message),
			'app' => uniqid('testapp_', true),
			);
	}

	// Validate details of the result
	function checkResult($result, $success) {
		// Check response is of correct type
		$this->assertInstanceOf('OC_OCS_Result', $result);
		// Check if it succeeded
		/** @var $result OC_OCS_Result */
		$this->assertEquals($success, $result->succeeded());
	}

	function dataProviderTestOneResult() {
		return array(
			array(100, true),
			array(101, false),
			array(997, false),
		);
	}

	/**
	 * @dataProvider dataProviderTestOneResult
	 *
	 * @param $statusCode
	 * @param $succeeded
	 */
	public function testOneResult($statusCode, $succeeded) {
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

		// Test merging one success result
		$response = $this->buildResponse(true, $data1, $statusCode);
		$result = OC_API::mergeResponses(array($response));
		$this->assertEquals($response['response'], $result);
		$this->checkResult($result, $succeeded);
	}

	function dataProviderTestMergeResponses() {
		return array(
			// Two shipped success results
			array(true, 100, true, 100, true),
			// Two shipped results, one success and one failure
			array(true, 100, true, 998, false),
			// Two shipped results, both failure
			array(true, 997, true, 998, false),
			// Two third party success results
			array(false, 100, false, 100, true),
			// Two third party results, one success and one failure
			array(false, 100, false, 998, false),
			// Two third party results, both failure
			array(false, 997, false, 998, false),
			// One of each, both success
			array(false, 100, true, 100, true),
			array(true, 100, false, 100, true),
			// One of each, both failure
			array(false, 997, true, 998, false),
			// One of each, shipped success
			array(false, 997, true, 100, true),
			// One of each, third party success
			array(false, 100, true, 998, false),
		);
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
		$result = OC_API::mergeResponses(array(
			$this->buildResponse($shipped1, $data1, $statusCode1, "message1"),
			$this->buildResponse($shipped2, $data2, $statusCode2, "message2"),
		));
		$this->checkResult($result, $succeeded);
		$resultData = $result->getData();
		$resultMeta = $result->getMeta();
		$resultStatusCode = $result->getStatusCode();

		$this->assertArrayHasKey('jan', $resultData['users']);

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
