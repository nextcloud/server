<?php
// This file was auto-generated from sdk-root/src/data/codedeploy/2014-10-06/waiters-2.json
return [ 'version' => 2, 'waiters' => [ 'DeploymentSuccessful' => [ 'delay' => 15, 'operation' => 'GetDeployment', 'maxAttempts' => 120, 'acceptors' => [ [ 'expected' => 'Succeeded', 'matcher' => 'path', 'state' => 'success', 'argument' => 'deploymentInfo.status', ], [ 'expected' => 'Failed', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'deploymentInfo.status', ], [ 'expected' => 'Stopped', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'deploymentInfo.status', ], ], ], ],];
