<?php
// This file was auto-generated from sdk-root/src/data/support/2013-04-15/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-east-1', 'testCases' => [ [ 'operationName' => 'DescribeServices', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'CreateCase', 'input' => [ 'subject' => 'subject', 'communicationBody' => 'communication', 'categoryCode' => 'category', 'serviceCode' => 'amazon-dynamodb', 'severityCode' => 'low', ], 'errorExpectedFromService' => true, ], ],];
