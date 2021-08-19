<?php
// This file was auto-generated from sdk-root/src/data/wafv2/2019-07-29/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-east-1', 'testCases' => [ [ 'operationName' => 'ListWebACLs', 'input' => [ 'Scope' => 'REGIONAL', 'Limit' => 20, ], 'errorExpectedFromService' => false, ], [ 'operationName' => 'CreateRegexPatternSet', 'input' => [ 'Name' => 'fake_name', 'Scope' => 'fake_scope', ], 'errorExpectedFromService' => true, ], ],];
