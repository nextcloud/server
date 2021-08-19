<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Tests;

require_once __DIR__ . '/Common/CompletionHandlerTestCase.php';

use Stecman\Component\Symfony\Console\BashCompletion\Completion;
use Stecman\Component\Symfony\Console\BashCompletion\Tests\Common\CompletionHandlerTestCase;

class CompletionTest extends CompletionHandlerTestCase
{
    /**
     * @dataProvider getCompletionTestInput
     */
    public function testCompletionResults($completions, $commandlineResultMap)
    {
        if (!is_array($completions)) {
            $completions = array($completions);
        }

        foreach ($commandlineResultMap as $commandLine => $result) {
            $handler = $this->createHandler($commandLine);
            $handler->addHandlers($completions);
            $this->assertEquals($result, $this->getTerms($handler->runCompletion()));
        }
    }

    public function getCompletionTestInput()
    {
        $options = array('smooth', 'latin', 'moody');

        return array(
            'command match' => array(
                new Completion(
                    'wave',
                    'target',
                    Completion::ALL_TYPES,
                    $options
                ),
                array(
                    'app walk:north --target ' => array(),
                    'app wave ' => $options
                )
            ),

            'type restriction option' => array(
                new Completion(
                    Completion::ALL_COMMANDS,
                    'target',
                    Completion::TYPE_OPTION,
                    $options
                ),
                array(
                    'app walk:north --target ' => $options,
                    'app wave ' => array()
                )
            ),

            'type restriction argument' => array(
                new Completion(
                    Completion::ALL_COMMANDS,
                    'target',
                    Completion::TYPE_ARGUMENT,
                    $options
                ),
                array(
                    'app walk:north --target ' => array(),
                    'app wave ' => $options
                )
            ),

            'makeGlobalHandler static' => array(
                Completion::makeGlobalHandler(
                    'target',
                    Completion::ALL_TYPES,
                    $options
                ),
                array(
                    'app walk:north --target ' => $options,
                    'app wave ' => $options
                )
            ),

            'with anonymous function' => array(
                new Completion(
                    'wave',
                    'style',
                    Completion::TYPE_OPTION,
                    function() {
                        return range(1, 5);
                    }
                ),
                array(
                    'app walk:north --target ' => array(),
                    'app wave ' => array(),
                    'app wave --style ' => array(1, 2,3, 4, 5)
                )
            ),

            'with callable array' => array(
                new Completion(
                    Completion::ALL_COMMANDS,
                    'target',
                    Completion::ALL_TYPES,
                    array($this, 'instanceMethodForCallableCheck')
                ),
                array(
                    'app walk:north --target ' => array('hello', 'world'),
                    'app wave ' => array('hello', 'world')
                )
            ),

            'multiple handlers' => array(
                array(
                    new Completion(
                        Completion::ALL_COMMANDS,
                        'target',
                        Completion::TYPE_OPTION,
                        array('all:option:target')
                    ),
                    new Completion(
                        Completion::ALL_COMMANDS,
                        'target',
                        Completion::ALL_TYPES,
                        array('all:all:target')
                    ),
                    new Completion(
                        Completion::ALL_COMMANDS,
                        'style',
                        Completion::TYPE_OPTION,
                        array('all:option:style')
                    ),
                ),
                array(
                    'app walk:north ' => array(),
                    'app walk:north -t ' => array('all:option:target'),
                    'app wave ' => array('all:all:target'),
                    'app wave bruce -s ' => array('all:option:style'),
                    'app walk:north --style ' => array('all:option:style'),
                )
            )
        );
    }

    /**
     * Used in the test "with callable array"
     * @return array
     */
    public function instanceMethodForCallableCheck()
    {
        return array('hello', 'world');
    }
}
