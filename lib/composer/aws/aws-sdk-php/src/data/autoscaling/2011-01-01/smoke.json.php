<?php
// This file was auto-generated from sdk-root/src/data/autoscaling/2011-01-01/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'DescribeScalingProcessTypes', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'CreateLaunchConfiguration', 'input' => [ 'LaunchConfigurationName' => 'hello, world', 'ImageId' => 'ami-12345678', 'InstanceType' => 'm1.small', ], 'errorExpectedFromService' => true, ], ],];
