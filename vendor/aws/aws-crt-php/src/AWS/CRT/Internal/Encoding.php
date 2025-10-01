<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Internal;

final class Encoding {
    public static function readString(&$buffer) {
        list($len, $str) = self::decodeString($buffer);
        // Advance by sizeof(length) + strlen(str)
        $buffer = substr($buffer, 4 + $len);
        return $str;
    }

    public static function readStrings($buffer) {
        $strings = [];
        while (strlen($buffer)) {
            $strings []= self::readString($buffer);
        }
        return $strings;
    }

    public static function decodeString($buffer) {
        $len = unpack("N", $buffer)[1];
        $buffer = substr($buffer, 4);
        $str = unpack("a{$len}", $buffer)[1];
        return [$len, $str];
    }

    public static function encodeString($str) {
        if (is_array($str)) {
            $str = $str[0];
        }
        return pack("Na*", strlen($str), $str);
    }
}
