<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Auth;

use AWS\CRT\NativeResource as NativeResource;
use AWS\CRT\Options as Options;

class SigningConfigAWS extends NativeResource {

    public static function defaults() {
        return [
            'algorithm' => SigningAlgorithm::SIGv4,
            'signature_type' => SignatureType::HTTP_REQUEST_HEADERS,
            'credentials_provider' => null,
            'region' => null,
            'service' => null,
            'use_double_uri_encode' => false,
            'should_normalize_uri_path' => false,
            'omit_session_token' => false,
            'signed_body_value' => null,
            'signed_body_header_type' => SignedBodyHeaderType::NONE,
            'expiration_in_seconds' => 0,
            'date' => time(),
            'should_sign_header' => null,
        ];
    }

    private $options;

    public function __construct(array $options = []) {
        parent::__construct();
        $this->options = $options = new Options($options, self::defaults());
        $sc = $this->acquire(self::$crt->signing_config_aws_new());
        self::$crt->signing_config_aws_set_algorithm($sc, $options->algorithm->asInt());
        self::$crt->signing_config_aws_set_signature_type($sc, $options->signature_type->asInt());
        if ($credentials_provider = $options->credentials_provider->asObject()) {
            self::$crt->signing_config_aws_set_credentials_provider(
                $sc,
                $credentials_provider->native);
        }
        self::$crt->signing_config_aws_set_region(
            $sc, $options->region->asString());
        self::$crt->signing_config_aws_set_service(
            $sc, $options->service->asString());
        self::$crt->signing_config_aws_set_use_double_uri_encode(
            $sc, $options->use_double_uri_encode->asBool());
        self::$crt->signing_config_aws_set_should_normalize_uri_path(
            $sc, $options->should_normalize_uri_path->asBool());
        self::$crt->signing_config_aws_set_omit_session_token(
            $sc, $options->omit_session_token->asBool());
        self::$crt->signing_config_aws_set_signed_body_value(
            $sc, $options->signed_body_value->asString());
        self::$crt->signing_config_aws_set_signed_body_header_type(
            $sc, $options->signed_body_header_type->asInt());
        self::$crt->signing_config_aws_set_expiration_in_seconds(
            $sc, $options->expiration_in_seconds->asInt());
        self::$crt->signing_config_aws_set_date($sc, $options->date->asInt());
        if ($should_sign_header = $options->should_sign_header->asCallable()) {
            self::$crt->signing_config_aws_set_should_sign_header_fn($sc, $should_sign_header);
        }
    }

    function __destruct()
    {
        self::$crt->signing_config_aws_release($this->release());
        parent::__destruct();
    }

    public function __get($name) {
        return $this->options->get($name);
    }
}