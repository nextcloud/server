<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Tests;

use PHPUnit\Framework\TestCase;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class CompletionCommandTest extends TestCase
{
    /**
     * Ensure conflicting options names and shortcuts from the application do not break the completion command
     */
    public function testConflictingGlobalOptions()
    {
        $app = new Application('Base application');

        // Conflicting option shortcut
        $app->getDefinition()->addOption(
            new InputOption('conflicting-shortcut', 'g', InputOption::VALUE_NONE)
        );

        // Conflicting option name
        $app->getDefinition()->addOption(
            new InputOption('program', null, InputOption::VALUE_REQUIRED)
        );

        $app->add(new CompletionCommand());

        // Check completion command doesn't throw
        $app->doRun(new StringInput('_completion -g --program foo'), new NullOutput());
        $app->doRun(new StringInput('_completion --help'), new NullOutput());
        $app->doRun(new StringInput('help _completion'), new NullOutput());

        // Check default options are available
        $app->doRun(new StringInput('_completion -V -vv --no-ansi --quiet'), new NullOutput());
    }
}
