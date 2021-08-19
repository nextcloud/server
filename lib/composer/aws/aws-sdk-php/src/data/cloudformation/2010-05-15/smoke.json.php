<?php
// This file was auto-generated from sdk-root/src/data/cloudformation/2010-05-15/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListStacks', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'CreateStack', 'input' => [ 'StackName' => 'fakestack', 'TemplateURL' => 'http://s3.amazonaws.com/foo/bar', ], 'errorExpectedFromService' => true, ], ],];
