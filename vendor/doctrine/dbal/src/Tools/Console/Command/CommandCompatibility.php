<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Tools\Console\Command;

use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// Symfony 8
if ((new ReflectionMethod(Command::class, 'configure'))->hasReturnType()) {
    /** @internal */
    trait CommandCompatibility
    {
        protected function configure(): void
        {
            $this->doConfigure();
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            return $this->doExecute($input, $output);
        }
    }
// Symfony 7
} elseif ((new ReflectionMethod(Command::class, 'execute'))->hasReturnType()) {
    /** @internal */
    trait CommandCompatibility
    {
        /** @return void */
        protected function configure()
        {
            $this->doConfigure();
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            return $this->doExecute($input, $output);
        }
    }
} else {
    /** @internal */
    trait CommandCompatibility
    {
        /** @return void */
        protected function configure()
        {
            $this->doConfigure();
        }

        /**
         * {@inheritDoc}
         *
         * @return int
         */
        protected function execute(InputInterface $input, OutputInterface $output)
        {
            return $this->doExecute($input, $output);
        }
    }
}
