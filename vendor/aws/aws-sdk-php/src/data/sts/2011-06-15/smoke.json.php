<?php
// This file was auto-generated from sdk-root/src/data/sts/2011-06-15/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'GetSessionToken', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'GetFederationToken', 'input' => [ 'Name' => 'temp', 'Policy' => '{\\"temp\\":true}', ], 'errorExpectedFromService' => true, ], ],];
