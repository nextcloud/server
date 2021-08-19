<?php

class TestSymfonyStyleCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName('walk:north')
            ->addOption(
                'power',
                'p'
            )
            ->addOption(
                'deploy:jazz-hands',
                'j'
            )
            ->addOption(
                'style',
                's',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'target',
                't',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED
            );
    }
}
