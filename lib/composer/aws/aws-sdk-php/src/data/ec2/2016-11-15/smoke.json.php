<?php
// This file was auto-generated from sdk-root/src/data/ec2/2016-11-15/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'DescribeRegions', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'DescribeInstances', 'input' => [ 'InstanceIds' => [ 'i-12345678', ], ], 'errorExpectedFromService' => true, ], ],];
