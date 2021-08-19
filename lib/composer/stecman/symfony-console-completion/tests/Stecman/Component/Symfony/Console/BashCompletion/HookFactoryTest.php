<?php

namespace Stecman\Component\Symfony\Console\BashCompletion\Tests;

use PHPUnit\Framework\TestCase;
use Stecman\Component\Symfony\Console\BashCompletion\HookFactory;

class HookFactoryTest extends TestCase
{
    /**
     * @var HookFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new HookFactory();
    }

    /**
     * @dataProvider generateHookDataProvider
     */
    public function testBashSyntax($programPath, $programName, $multiple)
    {
        if ($this->hasProgram('bash')) {
            $script = $this->factory->generateHook('bash', $programPath, $programName, $multiple);
            $this->assertSyntaxIsValid($script, 'bash -n', 'BASH hook');
        } else {
            $this->markTestSkipped("Couldn't detect BASH program to run hook syntax check");
        }
    }

    /**
     * @dataProvider generateHookDataProvider
     */
    public function testZshSyntax($programPath, $programName, $multiple)
    {
        if ($this->hasProgram('zsh')) {
            $script = $this->factory->generateHook('zsh', $programPath, $programName, $multiple);
            $this->assertSyntaxIsValid($script, 'zsh -n', 'ZSH hook');
        } else {
            $this->markTestSkipped("Couldn't detect ZSH program to run hook syntax check");
        }
    }

    public function generateHookDataProvider()
    {
        return array(
            array('/path/to/myprogram', null, false),
            array('/path/to/myprogram', null, true),
            array('/path/to/myprogram', 'myprogram', false),
            array('/path/to/myprogram', 'myprogram', true),
            array('/path/to/my-program', 'my-program', false)
        );
    }

    public function testForMissingSemiColons()
    {
        $class = new \ReflectionClass('Stecman\Component\Symfony\Console\BashCompletion\HookFactory');
        $properties = $class->getStaticProperties();
        $hooks = $properties['hooks'];

        // Check each line is commented or closed correctly to be collapsed for eval
        foreach ($hooks as $shellType => $hook) {
            $line = strtok($hook, "\n");
            $lineNumber = 0;

            while ($line !== false) {
                $lineNumber++;

                if (!$this->isScriptLineValid($line)) {
                    $this->fail("$shellType hook appears to be missing a semicolon on line $lineNumber:\n> $line");
                }

                $line = strtok("\n");
            }
        }
    }

    /**
     * Check if a line of shell script is safe to be collapsed to one line for eval
     */
    protected function isScriptLineValid($line)
    {
        if (preg_match('/^\s*#/', $line)) {
            // Line is commented out
            return true;
        }

        if (preg_match('/[;\{\}]\s*$/', $line)) {
            // Line correctly ends with a semicolon or syntax
            return true;
        }

        if (preg_match('
                    /(
                        ;\s*then |
                        \s*else
                    )
                    \s*$
                    /x', $line)
        ) {
            // Line ends with another permitted sequence
            return true;
        }

        return false;
    }

    protected function hasProgram($programName)
    {
        exec(sprintf(
            'command -v %s',
            escapeshellarg($programName)
        ), $output, $return);

        return $return === 0;
    }

    /**
     * @param string $code - code to pipe to the syntax checking command
     * @param string $syntaxCheckCommand - equivalent to `bash -n`.
     * @param string $context - what the syntax check is for
     */
    protected function assertSyntaxIsValid($code, $syntaxCheckCommand, $context)
    {
        $process = proc_open(
            escapeshellcmd($syntaxCheckCommand),
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ),
            $pipes
        );

        if (is_resource($process)) {
            // Push code into STDIN for the syntax checking process
            fwrite($pipes[0], $code);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $status = proc_close($process);

            $this->assertSame(0, $status, "Syntax check for $context failed:\n$output");
        } else {
            throw new \RuntimeException("Failed to start process with command '$syntaxCheckCommand'");
        }
    }
}
