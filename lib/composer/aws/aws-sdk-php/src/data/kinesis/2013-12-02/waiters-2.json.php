<?php
// This file was auto-generated from sdk-root/src/data/kinesis/2013-12-02/waiters-2.json
return [ 'version' => 2, 'waiters' => [ 'StreamExists' => [ 'delay' => 10, 'operation' => 'DescribeStream', 'maxAttempts' => 18, 'acceptors' => [ [ 'expected' => 'ACTIVE', 'matcher' => 'path', 'state' => 'success', 'argument' => 'StreamDescription.StreamStatus', ], ], ], 'StreamNotExists' => [ 'delay' => 10, 'operation' => 'DescribeStream', 'maxAttempts' => 18, 'acceptors' => [ [ 'expected' => 'ResourceNotFoundException', 'matcher' => 'error', 'state' => 'success', ], ], ], ],];
