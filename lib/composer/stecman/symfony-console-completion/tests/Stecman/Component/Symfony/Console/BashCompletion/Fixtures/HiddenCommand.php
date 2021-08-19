<?php


class HiddenCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName('internals')
            ->setHidden(true);
    }
}