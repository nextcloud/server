<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Konstantin Myakshin <molodchick@gmail.com>
 */
abstract class AbstractTransportFactory implements TransportFactoryInterface
{
    protected $dispatcher;
    protected $client;
    protected $logger;

    public function __construct(?EventDispatcherInterface $dispatcher = null, ?HttpClientInterface $client = null, ?LoggerInterface $logger = null)
    {
        $this->dispatcher = $dispatcher;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function supports(Dsn $dsn): bool
    {
        return \in_array($dsn->getScheme(), $this->getSupportedSchemes());
    }

    abstract protected function getSupportedSchemes(): array;

    protected function getUser(Dsn $dsn): string
    {
        return $dsn->getUser() ?? throw new IncompleteDsnException('User is not set.');
    }

    protected function getPassword(Dsn $dsn): string
    {
        return $dsn->getPassword() ?? throw new IncompleteDsnException('Password is not set.');
    }
}
