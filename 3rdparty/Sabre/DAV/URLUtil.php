<?php

/**
 * URL utility class
 *
 * This class provides methods to deal with encoding and decoding url (percent encoded) strings.
 *
 * It was not possible to use PHP's built-in methods for this, because some clients don't like
 * encoding of certain characters.
 *
 * Specifically, it was found that GVFS (gnome's webdav client) does not like encoding of ( and
 * ). Since these are reserved, but don't have a reserved meaning in url, these characters are
 * kept as-is.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_URLUtil {

    /**
     * Encodes the path of a url.
     *
     * slashes (/) are treated as path-separators.
     *
     * @param string $path
     * @return string
     */
    static function encodePath($path) {

        return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\)\/])/',function($match) {

            return '%'.sprintf('%02x',ord($match[0]));

        }, $path);

    }

    /**
     * Encodes a 1 segment of a path
     *
     * Slashes are considered part of the name, and are encoded as %2f
     *
     * @param string $pathSegment
     * @return string
     */
    static function encodePathSegment($pathSegment) {

        return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\)])/',function($match) {

            return '%'.sprintf('%02x',ord($match[0]));

        }, $pathSegment);
    }

    /**
     * Decodes a url-encoded path
     *
     * @param string $path
     * @return string
     */
    static function decodePath($path) {

        return self::decodePathSegment($path);

    }

    /**
     * Decodes a url-encoded path segment
     *
     * @param string $path
     * @return string
     */
    static function decodePathSegment($path) {

        $path = rawurldecode($path);
        $encoding = mb_detect_encoding($path, array('UTF-8','ISO-8859-1'));

        switch($encoding) {

            case 'ISO-8859-1' :
                $path = utf8_encode($path);

        }

        return $path;

    }

    /**
     * Returns the 'dirname' and 'basename' for a path.
     *
     * The reason there is a custom function for this purpose, is because
     * basename() is locale aware (behaviour changes if C locale or a UTF-8 locale is used)
     * and we need a method that just operates on UTF-8 characters.
     *
     * In addition basename and dirname are platform aware, and will treat backslash (\) as a
     * directory separator on windows.
     *
     * This method returns the 2 components as an array.
     *
     * If there is no dirname, it will return an empty string. Any / appearing at the end of the
     * string is stripped off.
     *
     * @param string $path
     * @return array
     */
    static function splitPath($path) {

        $matches = array();
        if(preg_match('/^(?:(?:(.*)(?:\/+))?([^\/]+))(?:\/?)$/u',$path,$matches)) {
            return array($matches[1],$matches[2]);
        } else {
            return array(null,null);
        }

    }

}
