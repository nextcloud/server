<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Auth;

use AWS\CRT\NativeResource as NativeResource;
use AWS\CRT\Options as Options;

/**
 * Represents a set of AWS credentials
 *
 * @param array options:
 * - string access_key_id - AWS Access Key Id
 * - string secret_access_key - AWS Secret Access Key
 * - string session_token - Optional STS session token
 * - int expiration_timepoint_seconds - Optional time to expire these credentials
 */
final class AwsCredentials extends NativeResource {

    static function defaults() {
        return [
            'access_key_id' => '',
            'secret_access_key' => '',
            'session_token' => '',
            'expiration_timepoint_seconds' => 0,
        ];
    }

    private $access_key_id;
    private $secret_access_key;
    private $session_token;
    private $expiration_timepoint_seconds = 0;

    public function __get($name) {
        return $this->$name;
    }

    function __construct(array $options = []) {
        parent::__construct();

        $options = new Options($options, self::defaults());
        $this->access_key_id = $options->access_key_id->asString();
        $this->secret_access_key = $options->secret_access_key->asString();
        $this->session_token = $options->session_token ? $options->session_token->asString() : null;
        $this->expiration_timepoint_seconds = $options->expiration_timepoint_seconds->asInt();

        if (strlen($this->access_key_id) == 0) {
            throw new \InvalidArgumentException("access_key_id must be provided");
        }
        if (strlen($this->secret_access_key) == 0) {
            throw new \InvalidArgumentException("secret_access_key must be provided");
        }

        $creds_options = self::$crt->aws_credentials_options_new();
        self::$crt->aws_credentials_options_set_access_key_id($creds_options, $this->access_key_id);
        self::$crt->aws_credentials_options_set_secret_access_key($creds_options, $this->secret_access_key);
        self::$crt->aws_credentials_options_set_session_token($creds_options, $this->session_token);
        self::$crt->aws_credentials_options_set_expiration_timepoint_seconds($creds_options, $this->expiration_timepoint_seconds);
        $this->acquire(self::$crt->aws_credentials_new($creds_options));
        self::$crt->aws_credentials_options_release($creds_options);
    }

    function __destruct() {
        self::$crt->aws_credentials_release($this->release());
        parent::__destruct();
    }
}
