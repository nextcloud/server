<?php
// This file was auto-generated from sdk-root/src/data/firehose/2015-08-04/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListDeliveryStreams', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'DescribeDeliveryStream', 'input' => [ 'DeliveryStreamName' => 'bogus-stream-name', ], 'errorExpectedFromService' => true, ], ],];
