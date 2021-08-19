<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Initializer;

use Doctrine\DBAL\Driver\Mysqli\Initializer;
use mysqli;

final class Secure implements Initializer
{
    /** @var string */
    private $key;

    /** @var string */
    private $cert;

    /** @var string */
    private $ca;

    /** @var string */
    private $capath;

    /** @var string */
    private $cipher;

    public function __construct(string $key, string $cert, string $ca, string $capath, string $cipher)
    {
        $this->key    = $key;
        $this->cert   = $cert;
        $this->ca     = $ca;
        $this->capath = $capath;
        $this->cipher = $cipher;
    }

    public function initialize(mysqli $connection): void
    {
        $connection->ssl_set($this->key, $this->cert, $this->ca, $this->capath, $this->cipher);
    }
}
