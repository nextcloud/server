<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\IO;

use AWS\CRT\NativeResource as NativeResource;

final class InputStream extends NativeResource {
    private $stream = null;

    const SEEK_BEGIN = 0;
    const SEEK_END = 2;

    public function __construct($stream) {
        parent::__construct();
        $this->stream = $stream;
        $options = self::$crt->input_stream_options_new();
        // The stream implementation in native just converts the PHP stream into
        // a native php_stream* and executes operations entirely in native
        self::$crt->input_stream_options_set_user_data($options, $stream);
        $this->acquire(self::$crt->input_stream_new($options));
        self::$crt->input_stream_options_release($options);
    }

    public function __destruct() {
        $this->release();
        parent::__destruct();
    }

    public function eof() {
        return self::$crt->input_stream_eof($this->native);
    }

    public function length() {
        return self::$crt->input_stream_get_length($this->native);
    }

    public function read($length = 0) {
        if ($length == 0) {
            $length = $this->length();
        }
        return self::$crt->input_stream_read($this->native, $length);
    }

    public function seek($offset, $basis) {
        return self::$crt->input_stream_seek($this->native, $offset, $basis);
    }
}
