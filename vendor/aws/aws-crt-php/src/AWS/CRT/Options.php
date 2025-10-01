<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT;

final class OptionValue {
    private $value;
    function __construct($value) {
        $this->value = $value;
    }

    public function asObject() {
        return $this->value;
    }

    public function asMixed() {
        return $this->value;
    }

    public function asInt() {
        return empty($this->value) ? 0 : (int)$this->value;
    }

    public function asBool() {
        return boolval($this->value);
    }

    public function asString() {
        return !empty($this->value) ? strval($this->value) : "";
    }

    public function asArray() {
        return is_array($this->value) ? $this->value : (!empty($this->value) ? [$this->value] : []);
    }

    public function asCallable() {
        return is_callable($this->value) ? $this->value : null;
    }
}

final class Options {
    private $options;

    public function __construct($opts = [], $defaults = []) {
        $this->options = array_replace($defaults, empty($opts) ? [] : $opts);
    }

    public function __get($name) {
        return $this->get($name);
    }

    public function asArray() {
        return $this->options;
    }

    public function toArray() {
        return array_merge_recursive([], $this->options);
    }

    public function get($name) {
        return new OptionValue($this->options[$name]);
    }

    public function getInt($name) {
        return $this->get($name)->asInt();
    }

    public function getString($name) {
        return $this->get($name)->asString();
    }

    public function getBool($name) {
        return $this->get($name)->asBool();
    }
}
