<?php
// This file was auto-generated from sdk-root/src/data/kinesis/2013-12-02/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListStreams', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'DescribeStream', 'input' => [ 'StreamName' => 'bogus-stream-name', ], 'errorExpectedFromService' => true, ], ],];
