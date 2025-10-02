<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Uid;

/**
 * A v4 UUID contains a 122-bit random number.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class UuidV4 extends Uuid
{
    protected const TYPE = 4;

    public function __construct(?string $uuid = null)
    {
        if (null === $uuid) {
            $uuid = random_bytes(16);
            $uuid[6] = $uuid[6] & "\x0F" | "\x40";
            $uuid[8] = $uuid[8] & "\x3F" | "\x80";
            $uuid = bin2hex($uuid);

            $this->uid = substr($uuid, 0, 8).'-'.substr($uuid, 8, 4).'-'.substr($uuid, 12, 4).'-'.substr($uuid, 16, 4).'-'.substr($uuid, 20, 12);
        } else {
            parent::__construct($uuid, true);
        }
    }
}
