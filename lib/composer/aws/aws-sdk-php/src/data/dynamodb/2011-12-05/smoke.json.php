<?php
// This file was auto-generated from sdk-root/src/data/dynamodb/2011-12-05/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListTables', 'input' => [ 'Limit' => 1, ], 'errorExpectedFromService' => false, ], [ 'operationName' => 'DescribeTable', 'input' => [ 'TableName' => 'fake-table', ], 'errorExpectedFromService' => true, ], ],];
