<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\HTTP;

use AWS\CRT\NativeResource;
use AWS\CRT\Internal\Encoding;

abstract class Message extends NativeResource {
    private $method;
    private $path;
    private $query;
    private $headers;

    public function __construct($method, $path, $query = [], $headers = []) {
        parent::__construct();
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->headers = new Headers($headers);
        $this->acquire(self::$crt->http_message_new_from_blob(self::marshall($this)));
    }

    public function __destruct() {
        self::$crt->http_message_release($this->release());
        parent::__destruct();
    }

    public function toBlob() {
        return self::$crt->http_message_to_blob($this->native);
    }

    protected static function marshall($msg) {
        $buf = "";
        $buf .= Encoding::encodeString($msg->method);
        $buf .= Encoding::encodeString($msg->pathAndQuery());
        $buf .= Headers::marshall($msg->headers);
        return $buf;
    }

    protected static function _unmarshall($buf, $class=Message::class) {
        $method = Encoding::readString($buf);
        $path_and_query = Encoding::readString($buf);
        $parts = explode("?", $path_and_query, 2);
        $path = isset($parts[0]) ? $parts[0] : "";
        $query = isset($parts[1]) ? $parts[1] : "";
        $headers = Headers::unmarshall($buf);

        // Turn query params back into a dictionary
        if (strlen($query)) {
            $query = rawurldecode($query);
            $query = explode("&", $query);
            $query = array_reduce($query, function($params, $pair) {
                list($param, $value) = explode("=", $pair, 2);
                $params[$param] = $value;
                return $params;
            }, []);
        } else {
            $query = [];
        }

        return new $class($method, $path, $query, $headers->toArray());
    }

    public function pathAndQuery() {
        $path = $this->path;
        $queries = [];
        foreach ($this->query as $param => $value) {
            $queries []= urlencode($param) . "=" . urlencode($value);
        }
        $query = implode("&", $queries);
        if (strlen($query)) {
            $path = implode("?", [$path, $query]);
        }
        return $path;
    }

    public function method() {
        return $this->method;
    }

    public function path() {
        return $this->path;
    }

    public function query() {
        return $this->query;
    }

    public function headers() {
        return $this->headers;
    }
}
