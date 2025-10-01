<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Auth;

use AWS\CRT\IO\InputStream;
use AWS\CRT\NativeResource as NativeResource;

class Signable extends NativeResource {

    public static function fromHttpRequest($http_message) {
        return new Signable(function() use ($http_message) {
            return self::$crt->signable_new_from_http_request($http_message->native);
        });
    }

    public static function fromChunk($chunk_stream, $previous_signature="") {
        if (!($chunk_stream instanceof InputStream)) {
            $chunk_stream = new InputStream($chunk_stream);
        }
        return new Signable(function() use($chunk_stream, $previous_signature) {
            return self::$crt->signable_new_from_chunk($chunk_stream->native, $previous_signature);
        });
    }

    public static function fromCanonicalRequest($canonical_request) {
        return new Signable(function() use($canonical_request) {
            return self::$crt->signable_new_from_canonical_request($canonical_request);
        });
    }

    protected function __construct($ctor) {
        parent::__construct();
        $this->acquire($ctor());
    }

    function __destruct() {
        self::$crt->signable_release($this->release());
        parent::__destruct();
    }
}