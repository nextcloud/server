<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Console;

use PhpCsFixer\Console\Command\DescribeCommand;
use PhpCsFixer\Console\Command\FixCommand;
use PhpCsFixer\Console\Command\HelpCommand;
use PhpCsFixer\Console\Command\ReadmeCommand;
use PhpCsFixer\Console\Command\SelfUpdateCommand;
use PhpCsFixer\Console\SelfUpdate\GithubClient;
use PhpCsFixer\Console\SelfUpdate\NewVersionChecker;
use PhpCsFixer\PharChecker;
use PhpCsFixer\ToolInfo;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class Application extends BaseApplication
{
    const VERSION = '2.16.3';
    const VERSION_CODENAME = 'Yellow Bird';

    /**
     * @var ToolInfo
     */
    private $toolInfo;

    public function __construct()
    {
        if (!getenv('PHP_CS_FIXER_FUTURE_MODE')) {
            error_reporting(-1);
        }

        parent::__construct('PHP CS Fixer', self::VERSION);

        $this->toolInfo = new ToolInfo();

        $this->add(new DescribeCommand());
        $this->add(new FixCommand($this->toolInfo));
        $this->add(new ReadmeCommand());
        $this->add(new SelfUpdateCommand(
            new NewVersionChecker(new GithubClient()),
            $this->toolInfo,
            new PharChecker()
        ));
    }

    /**
     * @return int
     */
    public static function getMajorVersion()
    {
        return (int) explode('.', self::VERSION)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $stdErr = $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : ($input->hasParameterOption('--format', true) && 'txt' !== $input->getParameterOption('--format', null, true) ? null : $output)
        ;
        if (null !== $stdErr) {
            $warningsDetector = new WarningsDetector($this->toolInfo);
            $warningsDetector->detectOldVendor();
            $warningsDetector->detectOldMajor();
            foreach ($warningsDetector->getWarnings() as $warning) {
                $stdErr->writeln(sprintf($stdErr->isDecorated() ? '<bg=yellow;fg=black;>%s</>' : '%s', $warning));
            }
        }

        return parent::doRun($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getLongVersion()
    {
        $version = sprintf(
            '%s <info>%s</info> by <comment>Fabien Potencier</comment> and <comment>Dariusz Ruminski</comment>',
            parent::getLongVersion(),
            self::VERSION_CODENAME
        );

        $commit = '@git-commit@';

        if ('@'.'git-commit@' !== $commit) {
            $version .= ' ('.substr($commit, 0, 7).')';
        }

        return $version;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        return [new HelpCommand(), new ListCommand()];
    }
}
