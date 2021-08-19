<?php
// This file was auto-generated from sdk-root/src/data/elastictranscoder/2012-09-25/waiters-1.json
return [ 'waiters' => [ 'JobComplete' => [ 'operation' => 'ReadJob', 'success_type' => 'output', 'success_path' => 'Job.Status', 'interval' => 30, 'max_attempts' => 120, 'success_value' => 'Complete', 'failure_value' => [ 'Canceled', 'Error', ], ], ],];
