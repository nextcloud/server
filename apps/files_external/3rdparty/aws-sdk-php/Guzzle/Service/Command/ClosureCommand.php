<?php

namespace Guzzle\Service\Command;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\UnexpectedValueException;
use Guzzle\Http\Message\RequestInterface;

/**
 * A ClosureCommand is a command that allows dynamic commands to be created at runtime using a closure to prepare the
 * request. A closure key and \Closure value must be passed to the command in the constructor. The closure must
 * accept the command object as an argument.
 */
class ClosureCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException if a closure was not passed
     */
    protected function init()
    {
        if (!$this['closure']) {
            throw new InvalidArgumentException('A closure must be passed in the parameters array');
        }
    }

    /**
     * {@inheritdoc}
     * @throws UnexpectedValueException If the closure does not return a request
     */
    protected function build()
    {
        $closure = $this['closure'];
        /** @var $closure \Closure */
        $this->request = $closure($this, $this->operation);

        if (!$this->request || !$this->request instanceof RequestInterface) {
            throw new UnexpectedValueException('Closure command did not return a RequestInterface object');
        }
    }
}
