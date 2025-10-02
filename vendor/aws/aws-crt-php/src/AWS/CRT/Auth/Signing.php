<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Auth;

use AWS\CRT\NativeResource;

abstract class Signing extends NativeResource {
    static function signRequestAws($signable, $signing_config, $on_complete) {
        return self::$crt->sign_request_aws($signable->native, $signing_config->native,
            function($result, $error_code) use ($on_complete) {
                $signing_result = SigningResult::fromNative($result);
                $on_complete($signing_result, $error_code);
            }, null);
    }

    static function testVerifySigV4ASigning($signable, $signing_config, $expected_canonical_request, $signature, $ecc_key_pub_x, $ecc_key_pub_y) {
        return self::$crt->test_verify_sigv4a_signing($signable, $signing_config, $expected_canonical_request, $signature, $ecc_key_pub_x, $ecc_key_pub_y);
    }
}
