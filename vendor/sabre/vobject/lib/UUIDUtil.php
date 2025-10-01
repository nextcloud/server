<?php

namespace Sabre\VObject;

/**
 * UUID Utility.
 *
 * This class has static methods to generate and validate UUID's.
 * UUIDs are used a decent amount within various *DAV standards, so it made
 * sense to include it.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class UUIDUtil
{
    /**
     * Returns a pseudo-random v4 UUID.
     *
     * This function is based on a comment by Andrew Moore on php.net
     *
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     *
     * @return string
     */
    public static function getUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Checks if a string is a valid UUID.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public static function validateUUID($uuid)
    {
        return 0 !== preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            $uuid
        );
    }
}
