<?php
// This file was auto-generated from sdk-root/src/data/ecr/2015-09-21/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'DescribeRepositories', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'ListImages', 'input' => [ 'repositoryName' => 'not-a-real-repository', ], 'errorExpectedFromService' => true, ], ],];
