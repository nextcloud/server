<?php
// This file was auto-generated from sdk-root/src/data/logs/2014-03-28/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'DescribeLogGroups', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'GetLogEvents', 'input' => [ 'logGroupName' => 'fakegroup', 'logStreamName' => 'fakestream', ], 'errorExpectedFromService' => true, ], ],];
