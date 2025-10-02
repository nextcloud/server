<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Internal;

use \RuntimeException;

/**
 * @internal
 * Forwards calls on to awscrt PHP extension functions
 */
final class Extension {
    function __construct() {
        if (!extension_loaded('awscrt')) {
            throw new RuntimeException('awscrt extension is not loaded');
        }
    }

    /**
     * Forwards any call made on this object to the extension function of the
     * same name with the supplied arguments. Argument type hinting and checking
     * occurs at the CRT wrapper.
     */
    function __call($name, $args) {
        return call_user_func_array($name, $args);
    }
}
