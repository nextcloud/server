<?php


class TestBasicCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName('wave')
            ->addOption(
                'vigorous'
            )
            ->addOption(
                'jazz-hands',
                'j'
            )
            ->addOption(
                'style',
                's',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED
            )
            ->addArgument(
                'target',
                \Symfony\Component\Console\Input\InputArgument::REQUIRED
            );
    }
}
