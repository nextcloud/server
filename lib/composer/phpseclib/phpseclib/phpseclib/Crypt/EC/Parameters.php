<?php

/**
 * EC Parameters
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\EC;

use phpseclib3\Crypt\EC;

/**
 * EC Parameters
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
final class Parameters extends EC
{
    /**
     * Returns the parameters
     *
     * @param string $type
     * @param array $options optional
     * @return string
     */
    public function toString($type = 'PKCS1', array $options = [])
    {
        $type = self::validatePlugin('Keys', 'PKCS1', 'saveParameters');

        return $type::saveParameters($this->curve, $options);
    }
}
