<?php
// This file was auto-generated from sdk-root/src/data/sns/2010-03-31/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListTopics', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'Publish', 'input' => [ 'Message' => 'hello', 'TopicArn' => 'fake_topic', ], 'errorExpectedFromService' => true, ], ],];
