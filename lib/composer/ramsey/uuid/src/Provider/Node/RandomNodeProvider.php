<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Provider\Node;

use Ramsey\Uuid\Exception\RandomSourceException;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Throwable;

use function bin2hex;
use function dechex;
use function hex2bin;
use function hexdec;
use function str_pad;
use function substr;

use const STR_PAD_LEFT;

/**
 * RandomNodeProvider generates a random node ID
 *
 * @link http://tools.ietf.org/html/rfc4122#section-4.5 RFC 4122, § 4.5: Node IDs that Do Not Identify the Host
 */
class RandomNodeProvider implements NodeProviderInterface
{
    public function getNode(): Hexadecimal
    {
        try {
            $nodeBytes = random_bytes(6);
        } catch (Throwable $exception) {
            throw new RandomSourceException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }

        // Split the node bytes for math on 32-bit systems.
        $nodeMsb = substr($nodeBytes, 0, 3);
        $nodeLsb = substr($nodeBytes, 3);

        // Set the multicast bit; see RFC 4122, section 4.5.
        $nodeMsb = hex2bin(
            str_pad(
                dechex(hexdec(bin2hex($nodeMsb)) | 0x010000),
                6,
                '0',
                STR_PAD_LEFT
            )
        );

        // Recombine the node bytes.
        $node = $nodeMsb . $nodeLsb;

        return new Hexadecimal(str_pad(bin2hex($node), 12, '0', STR_PAD_LEFT));
    }
}
