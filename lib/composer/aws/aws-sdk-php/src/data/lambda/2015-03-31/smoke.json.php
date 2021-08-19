<?php
// This file was auto-generated from sdk-root/src/data/lambda/2015-03-31/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListFunctions', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'Invoke', 'input' => [ 'FunctionName' => 'bogus-function', ], 'errorExpectedFromService' => true, ], ],];
