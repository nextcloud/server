<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Tests\Common;

use PHPUnit\Framework\TestCase;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionHandler;
use Symfony\Component\Console\Application;

/**
 * Base test case for running CompletionHandlers
 */
abstract class CompletionHandlerTestCase extends TestCase
{
    /**
     * @var Application
     */
    protected $application;

    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/../Fixtures/CompletionAwareCommand.php';
        require_once __DIR__ . '/../Fixtures/HiddenCommand.php';
        require_once __DIR__ . '/../Fixtures/TestBasicCommand.php';
        require_once __DIR__ . '/../Fixtures/TestSymfonyStyleCommand.php';
    }

    protected function setUp()
    {
        $this->application = new Application('Base application');
        $this->application->addCommands(array(
            new \CompletionAwareCommand(),
            new \TestBasicCommand(),
            new \TestSymfonyStyleCommand()
        ));

        if (method_exists('\HiddenCommand', 'setHidden')) {
            $this->application->add(new \HiddenCommand());
        }
    }

    /**
     * Create a handler set up with the given commandline and cursor position
     *
     * @param $commandLine
     * @param int $cursorIndex
     * @return CompletionHandler
     */
    protected function createHandler($commandLine, $cursorIndex = null)
    {
        $context = new CompletionContext();
        $context->setCommandLine($commandLine);
        $context->setCharIndex($cursorIndex === null ? strlen($commandLine) : $cursorIndex);

        return new CompletionHandler($this->application, $context);
    }

    /**
     * Get the list of terms from the output of CompletionHandler
     * The array index needs to be reset so that PHPUnit's array equality assertions match correctly.
     *
     * @param string $handlerOutput
     * @return string[]
     */
    protected function getTerms($handlerOutput)
    {
        return array_values($handlerOutput);
    }
}
