<?php
// This file was auto-generated from sdk-root/src/data/opsworkscm/2016-11-01/waiters-2.json
return [ 'version' => 2, 'waiters' => [ 'NodeAssociated' => [ 'delay' => 15, 'maxAttempts' => 15, 'operation' => 'DescribeNodeAssociationStatus', 'description' => 'Wait until node is associated or disassociated.', 'acceptors' => [ [ 'expected' => 'SUCCESS', 'state' => 'success', 'matcher' => 'path', 'argument' => 'NodeAssociationStatus', ], [ 'expected' => 'FAILED', 'state' => 'failure', 'matcher' => 'path', 'argument' => 'NodeAssociationStatus', ], ], ], ],];
