<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Exception;

class ParserException extends \Exception
{
    private $errorMessage;
    private $offset;

    public function __construct($errorMessage, $offset)
    {
        $this->errorMessage = $errorMessage;
        $this->offset = $offset;
        parent::__construct("ASN.1 Parser Exception at offset {$this->offset}: {$this->errorMessage}");
    }

    public function getOffset()
    {
        return $this->offset;
    }
}
