<?php
/**
 * Copyright (c) 2013 Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_API extends PHPUnit_Framework_TestCase {
	
	// Helps build a response variable
	public function buildResponse($shipped=true, $data=null, $code=100) {
		return array(
			'shipped' => $shipped,
			'response' => new OC_OCS_Result($data, $code),
			'app' => uniqid('testapp_', true),
			);
	}

	// Validate details of the result
	public function checkResult($result, $success=true) {
		// Check response is of correct type
		$this->assertEquals('OC_OCS_Result', get_class($result));
		// CHeck if it succeeded
		$this->assertEquals($success, $result->succeeded());
	}

	// Test the merging of multiple responses
	public function testMergeResponses(){
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
		// Test merging one success result
		$response = $this->buildResponse(true, $data1);
		$result = OC_API::mergeResponses(array($response));
		$this->assertEquals($response['response'], $result);
		$this->checkResult($result);

		$response = $this->buildResponse(true, $data1, 101);
		$result = OC_API::mergeResponses(array($response));
		$this->assertEquals($response['response'], $result);
		$this->checkResult($result);

		$response = $this->buildResponse(true, $data1, 997);
		$result = OC_API::mergeResponses(array($response));
		$this->assertEquals($response['response'], $result);
		$this->checkResult($result, false);

		// Two shipped success results
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(true, $data1O),
			$this->buildResponse(true, $data2),
			));
		$this->checkResult($result);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// Two shipped results, one success and one failure
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(true, $data1),
			$this->buildResponse(true, $data2, 997),
			));
		$this->checkResult($result, false);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// Two shipped results, both failure
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(true, $data1, 997),
			$this->buildResponse(true, $data2, 997),
			));
		$this->checkResult($result, false);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// Two third party success results
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1),
			$this->buildResponse(false, $data2),
			));
		$this->checkResult($result);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// Two third party results, one success and one failure
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1),
			$this->buildResponse(false, $data2, 997),
			));
		$this->checkResult($result, false);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// Two third party results, both failure
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1, 997),
			$this->buildResponse(false, $data2, 997),
			));
		$this->checkResult($result, false);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// One of each, both success
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1),
			$this->buildResponse(true, $data2),
			));
		$this->checkResult($result);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// One of each, both failure
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1, 997),
			$this->buildResponse(true, $data2, 997),
			));
		$this->checkResult($result, false);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// One of each, shipped success
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1, 997),
			$this->buildResponse(true, $data2),
			));
		$this->checkResult($result);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

		// One of each, third party success
		$result = OC_API::mergeResponses(array(
			$this->buildResponse(false, $data1),
			$this->buildResponse(true, $data2, 997),
			));
		$this->checkResult($result, false);
		$resultData = $result->getData();
		$this->assertArrayHasKey('jan', $resultData['users']);

	}

}
