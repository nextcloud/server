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

namespace PhpCsFixer\Fixer\Casing;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class MagicMethodCasingFixer extends AbstractFixer
{
    private static $magicNames = [
        '__call' => '__call',
        '__callstatic' => '__callStatic',
        '__clone' => '__clone',
        '__construct' => '__construct',
        '__debuginfo' => '__debugInfo',
        '__destruct' => '__destruct',
        '__get' => '__get',
        '__invoke' => '__invoke',
        '__isset' => '__isset',
        '__serialize' => '__serialize',
        '__set' => '__set',
        '__set_state' => '__set_state',
        '__sleep' => '__sleep',
        '__tostring' => '__toString',
        '__unserialize' => '__unserialize',
        '__unset' => '__unset',
        '__wakeup' => '__wakeup',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Magic method definitions and calls must be using the correct casing.',
            [
                new CodeSample(
                    '<?php
class Foo
{
    public function __Sleep()
    {
    }
}
'
                ),
                new CodeSample(
                    '<?php
$foo->__INVOKE(1);
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING) && $tokens->isAnyTokenKindsFound([T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $inClass = 0;
        $tokenCount = \count($tokens);

        for ($index = 1; $index < $tokenCount - 2; ++$index) {
            if (0 === $inClass && $tokens[$index]->isClassy()) {
                $inClass = 1;
                $index = $tokens->getNextTokenOfKind($index, ['{']);

                continue;
            }

            if (0 !== $inClass) {
                if ($tokens[$index]->equals('{')) {
                    ++$inClass;

                    continue;
                }

                if ($tokens[$index]->equals('}')) {
                    --$inClass;

                    continue;
                }
            }

            if (!$tokens[$index]->isGivenKind(T_STRING)) {
                continue; // wrong type
            }

            $content = $tokens[$index]->getContent();
            if ('__' !== substr($content, 0, 2)) {
                continue; // cheap look ahead
            }

            $name = strtolower($content);

            if (!$this->isMagicMethodName($name)) {
                continue; // method name is not one of the magic ones we can fix
            }

            $nameInCorrectCasing = $this->getMagicMethodNameInCorrectCasing($name);
            if ($nameInCorrectCasing === $content) {
                continue; // method name is already in the correct casing, no fix needed
            }

            if ($this->isFunctionSignature($tokens, $index)) {
                if (0 !== $inClass) {
                    // this is a method definition we want to fix
                    $this->setTokenToCorrectCasing($tokens, $index, $nameInCorrectCasing);
                }

                continue;
            }

            if ($this->isMethodCall($tokens, $index)) {
                $this->setTokenToCorrectCasing($tokens, $index, $nameInCorrectCasing);

                continue;
            }

            if (
                ('__callstatic' === $name || '__set_state' === $name)
                && $this->isStaticMethodCall($tokens, $index)
            ) {
                $this->setTokenToCorrectCasing($tokens, $index, $nameInCorrectCasing);
            }
        }
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isFunctionSignature(Tokens $tokens, $index)
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevIndex]->isGivenKind(T_FUNCTION)) {
            return false; // not a method signature
        }

        return $tokens[$tokens->getNextMeaningfulToken($index)]->equals('(');
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isMethodCall(Tokens $tokens, $index)
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevIndex]->equals([T_OBJECT_OPERATOR, '->'])) {
            return false; // not a "simple" method call
        }

        return $tokens[$tokens->getNextMeaningfulToken($index)]->equals('(');
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isStaticMethodCall(Tokens $tokens, $index)
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevIndex]->isGivenKind(T_DOUBLE_COLON)) {
            return false; // not a "simple" static method call
        }

        return $tokens[$tokens->getNextMeaningfulToken($index)]->equals('(');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isMagicMethodName($name)
    {
        return isset(self::$magicNames[$name]);
    }

    /**
     * @param string $name name of a magic method
     *
     * @return string
     */
    private function getMagicMethodNameInCorrectCasing($name)
    {
        return self::$magicNames[$name];
    }

    /**
     * @param int    $index
     * @param string $nameInCorrectCasing
     */
    private function setTokenToCorrectCasing(Tokens $tokens, $index, $nameInCorrectCasing)
    {
        $tokens[$index] = new Token([T_STRING, $nameInCorrectCasing]);
    }
}
