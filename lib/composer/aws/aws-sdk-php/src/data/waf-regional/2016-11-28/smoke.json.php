<?php
// This file was auto-generated from sdk-root/src/data/waf-regional/2016-11-28/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-east-1', 'testCases' => [ [ 'operationName' => 'ListRules', 'input' => [ 'Limit' => 20, ], 'errorExpectedFromService' => false, ], [ 'operationName' => 'CreateSqlInjectionMatchSet', 'input' => [ 'Name' => 'fake_name', 'ChangeToken' => 'fake_token', ], 'errorExpectedFromService' => true, ], ],];
