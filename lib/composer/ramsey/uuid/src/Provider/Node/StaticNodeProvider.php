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

use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Type\Hexadecimal;

use function dechex;
use function hexdec;
use function str_pad;
use function substr;

use const STR_PAD_LEFT;

/**
 * StaticNodeProvider provides a static node value with the multicast bit set
 *
 * @link http://tools.ietf.org/html/rfc4122#section-4.5 RFC 4122, § 4.5: Node IDs that Do Not Identify the Host
 */
class StaticNodeProvider implements NodeProviderInterface
{
    /**
     * @var Hexadecimal
     */
    private $node;

    /**
     * @param Hexadecimal $node The static node value to use
     */
    public function __construct(Hexadecimal $node)
    {
        if (strlen($node->toString()) > 12) {
            throw new InvalidArgumentException(
                'Static node value cannot be greater than 12 hexadecimal characters'
            );
        }

        $this->node = $this->setMulticastBit($node);
    }

    public function getNode(): Hexadecimal
    {
        return $this->node;
    }

    /**
     * Set the multicast bit for the static node value
     */
    private function setMulticastBit(Hexadecimal $node): Hexadecimal
    {
        $nodeHex = str_pad($node->toString(), 12, '0', STR_PAD_LEFT);
        $firstOctet = substr($nodeHex, 0, 2);

        $firstOctet = str_pad(
            dechex(hexdec($firstOctet) | 0x01),
            2,
            '0',
            STR_PAD_LEFT
        );

        return new Hexadecimal($firstOctet . substr($nodeHex, 2));
    }
}
