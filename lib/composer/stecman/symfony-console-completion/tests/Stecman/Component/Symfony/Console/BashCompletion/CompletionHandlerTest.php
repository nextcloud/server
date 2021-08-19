<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Tests;

require_once __DIR__ . '/Common/CompletionHandlerTestCase.php';

use Stecman\Component\Symfony\Console\BashCompletion\Completion;
use Stecman\Component\Symfony\Console\BashCompletion\Tests\Common\CompletionHandlerTestCase;

class CompletionHandlerTest extends CompletionHandlerTestCase
{
    public function testCompleteAppName()
    {
        $handler = $this->createHandler('app');

        // It's not valid to complete the application name, so this should return nothing
        $this->assertEmpty($handler->runCompletion());
    }

    public function testCompleteCommandNames()
    {
        $handler = $this->createHandler('app ');
        $this->assertEquals(
            array('help', 'list', 'completion-aware', 'wave', 'walk:north'),
            $this->getTerms($handler->runCompletion())
        );
    }

    public function testCompleteCommandNameNonMatch()
    {
        $handler = $this->createHandler('app br');
        $this->assertEmpty($handler->runCompletion());
    }

    public function testCompleteCommandNamePartialTwoMatches()
    {
        $handler = $this->createHandler('app wa');
        $this->assertEquals(array('wave', 'walk:north'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteCommandNamePartialOneMatch()
    {
        $handler = $this->createHandler('app wav');
        $this->assertEquals(array('wave'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteCommandNameFull()
    {
        $handler = $this->createHandler('app wave');

        // Completing on a matching word should return that word so that completion can continue
        $this->assertEquals(array('wave'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteSingleDash()
    {
        $handler = $this->createHandler('app wave -');

        // Short options are not given as suggestions
        $this->assertEmpty($handler->runCompletion());
    }

    public function testCompleteOptionShortcut()
    {
        $handler = $this->createHandler('app wave -j');

        // If a valid option shortcut is completed on, the shortcut is returned so that completion can continue
        $this->assertEquals(array('-j'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteOptionShortcutFirst()
    {
        // Check command options complete
        $handler = $this->createHandler('app -v wave --');
        $this->assertArraySubset(array('--vigorous', '--jazz-hands'), $this->getTerms($handler->runCompletion()));

        // Check unambiguous command name still completes
        $handler = $this->createHandler('app --quiet wav');
        $this->assertEquals(array('wave'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteDoubleDash()
    {
        $handler = $this->createHandler('app wave --');
        $this->assertArraySubset(array('--vigorous', '--jazz-hands'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteOptionFull()
    {
        $handler = $this->createHandler('app wave --jazz');
        $this->assertArraySubset(array('--jazz-hands'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteOptionEqualsValue()
    {
        // Cursor at the "=" sign
        $handler = $this->createHandler('app completion-aware --option-with-suggestions=');
        $this->assertEquals(array('one-opt', 'two-opt'), $this->getTerms($handler->runCompletion()));

        // Cursor at an opening quote
        $handler = $this->createHandler('app completion-aware --option-with-suggestions="');
        $this->assertEquals(array('one-opt', 'two-opt'), $this->getTerms($handler->runCompletion()));

        // Cursor inside a quote with value
        $handler = $this->createHandler('app completion-aware --option-with-suggestions="two');
        $this->assertEquals(array('two-opt'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteOptionOrder()
    {
        // Completion of options should be able to happen anywhere after the command name
        $handler = $this->createHandler('app wave bruce --vi');
        $this->assertEquals(array('--vigorous'), $this->getTerms($handler->runCompletion()));

        // Completing an option mid-commandline should work as normal
        $handler = $this->createHandler('app wave --vi --jazz-hands bruce', 13);
        $this->assertEquals(array('--vigorous'), $this->getTerms($handler->runCompletion()));
    }

    public function testCompleteColonCommand()
    {
        // Normal bash behaviour is to count the colon character as a word break
        // Since a colon is used to namespace Symfony Framework console commands the
        // character in a command name should not be taken as a word break
        //
        // @see https://github.com/stecman/symfony-console-completion/pull/1
        $handler = $this->createHandler('app walk');
        $this->assertEquals(array('walk:north'), $this->getTerms($handler->runCompletion()));

        $handler = $this->createHandler('app walk:north');
        $this->assertEquals(array('walk:north'), $this->getTerms($handler->runCompletion()));

        $handler = $this->createHandler('app walk:north --deploy');
        $this->assertEquals(array('--deploy:jazz-hands'), $this->getTerms($handler->runCompletion()));
    }

    /**
     * @dataProvider completionAwareCommandDataProvider
     */
    public function testCompletionAwareCommand($commandLine, array $suggestions)
    {
        $handler = $this->createHandler($commandLine);
        $this->assertSame($suggestions, $this->getTerms($handler->runCompletion()));
    }

    public function completionAwareCommandDataProvider()
    {
        return array(
            'not complete aware command' => array('app wave --vigorous ', array()),
            'argument suggestions' => array('app completion-aware any-arg ', array('one-arg', 'two-arg')),
            'argument no suggestions' => array('app completion-aware ', array()),
            'argument suggestions + context' => array('app completion-aware any-arg one', array('one-arg', 'one-arg-context')),
            'array argument suggestions' => array('app completion-aware any-arg one-arg array-arg1 ', array('one-arg', 'two-arg')),
            'array argument suggestions + context' => array('app completion-aware any-arg one-arg array-arg1 one', array('one-arg', 'one-arg-context')),
            'option suggestions' => array('app completion-aware --option-with-suggestions ', array('one-opt', 'two-opt')),
            'option no suggestions' => array('app completion-aware --option-without-suggestions ', array()),
            'option suggestions + context' => array(
                'app completion-aware --option-with-suggestions one', array('one-opt', 'one-opt-context')
            ),
        );
    }

    public function testShortCommandMatched()
    {
        $handler = $this->createHandler('app w:n --deploy');
        $this->assertEquals(array('--deploy:jazz-hands'), $this->getTerms($handler->runCompletion()));
    }

    public function testShortCommandNotMatched()
    {
        $handler = $this->createHandler('app w --deploy');
        $this->assertEquals(array(), $this->getTerms($handler->runCompletion()));
    }

    public function testHelpCommandCompletion()
    {
        $handler = $this->createHandler('app help ');
        $this->assertEquals(
            array('help', 'list', 'completion-aware', 'wave', 'walk:north'),
            $this->getTerms($handler->runCompletion())
        );
    }

    public function testListCommandCompletion()
    {
        $handler = $this->createHandler('app list ');
        $this->assertEquals(
            array('walk'),
            $this->getTerms($handler->runCompletion())
        );
    }
}
