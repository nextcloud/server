<?php
// This file was auto-generated from sdk-root/src/data/email/2010-12-01/waiters-1.json
return [ 'waiters' => [ '__default__' => [ 'interval' => 3, 'max_attempts' => 20, ], 'IdentityExists' => [ 'operation' => 'GetIdentityVerificationAttributes', 'success_type' => 'output', 'success_path' => 'VerificationAttributes[].VerificationStatus', 'success_value' => true, ], ],];
