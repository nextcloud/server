<?php
// This file was auto-generated from sdk-root/src/data/elasticloadbalancing/2012-06-01/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'DescribeLoadBalancers', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'DescribeLoadBalancers', 'input' => [ 'LoadBalancerNames' => [ 'fake_load_balancer', ], ], 'errorExpectedFromService' => true, ], ],];
