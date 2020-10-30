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

namespace PhpCsFixer\Fixer\LanguageConstruct;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class FunctionToConstantFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @var array<string, Token[]>
     */
    private static $availableFunctions;

    /**
     * @var array<string, Token[]>
     */
    private $functionsFixMap;

    public function __construct()
    {
        if (null === self::$availableFunctions) {
            self::$availableFunctions = [
                'get_called_class' => [
                    new Token([T_STATIC, 'static']),
                    new Token([T_DOUBLE_COLON, '::']),
                    new Token([CT::T_CLASS_CONSTANT, 'class']),
                ],
                'get_class' => [new Token([T_CLASS_C, '__CLASS__'])],
                'get_class_this' => [
                    new Token([T_STATIC, 'static']),
                    new Token([T_DOUBLE_COLON, '::']),
                    new Token([CT::T_CLASS_CONSTANT, 'class']),
                ],
                'php_sapi_name' => [new Token([T_STRING, 'PHP_SAPI'])],
                'phpversion' => [new Token([T_STRING, 'PHP_VERSION'])],
                'pi' => [new Token([T_STRING, 'M_PI'])],
            ];
        }

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->functionsFixMap = [];
        foreach ($this->configuration['functions'] as $key) {
            $this->functionsFixMap[$key] = self::$availableFunctions[$key];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replace core functions calls returning constants with the constants.',
            [
                new CodeSample(
                    "<?php\necho phpversion();\necho pi();\necho php_sapi_name();\nclass Foo\n{\n    public function Bar()\n    {\n        echo get_class();\n        echo get_called_class();\n    }\n}\n"
                ),
                new CodeSample(
                    "<?php\necho phpversion();\necho pi();\nclass Foo\n{\n    public function Bar()\n    {\n        echo get_class();\n        get_class(\$this);\n        echo get_called_class();\n    }\n}\n",
                    ['functions' => ['get_called_class', 'get_class_this', 'phpversion']]
                ),
            ],
            null,
            'Risky when any of the configured functions to replace are overridden.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NativeFunctionCasingFixer, NoExtraBlankLinesFixer, NoSinglelineWhitespaceBeforeSemicolonsFixer, NoTrailingWhitespaceFixer, NoWhitespaceInBlankLineFixer, SelfStaticAccessorFixer.
     * Must run after NoSpacesAfterFunctionNameFixer, NoSpacesInsideParenthesisFixer.
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $functionAnalyzer = new FunctionsAnalyzer();

        for ($index = $tokens->count() - 4; $index > 0; --$index) {
            $candidate = $this->getReplaceCandidate($tokens, $functionAnalyzer, $index);
            if (null === $candidate) {
                continue;
            }

            $this->fixFunctionCallToConstant(
                $tokens,
                $index,
                $candidate[0], // brace open
                $candidate[1], // brace close
                $candidate[2]  // replacement
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $functionNames = array_keys(self::$availableFunctions);

        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('functions', 'List of function names to fix.'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([new AllowedValueSubset($functionNames)])
                ->setDefault([
                    'get_class',
                    'php_sapi_name',
                    'phpversion',
                    'pi',
                    // TODO on v3.0 add 'get_called_class' and `get_class_this` here
                ])
                ->getOption(),
        ]);
    }

    /**
     * @param int     $index
     * @param int     $braceOpenIndex
     * @param int     $braceCloseIndex
     * @param Token[] $replacements
     */
    private function fixFunctionCallToConstant(Tokens $tokens, $index, $braceOpenIndex, $braceCloseIndex, array $replacements)
    {
        for ($i = $braceCloseIndex; $i >= $braceOpenIndex; --$i) {
            if ($tokens[$i]->equalsAny([[T_WHITESPACE], [T_COMMENT], [T_DOC_COMMENT]])) {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($i);
        }

        if ($replacements[0]->isGivenKind([T_CLASS_C, T_STATIC])) {
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            $prevToken = $tokens[$prevIndex];
            if ($prevToken->isGivenKind(T_NS_SEPARATOR)) {
                $tokens->clearAt($prevIndex);
            }
        }

        $tokens->clearAt($index);
        $tokens->insertAt($index, $replacements);
    }

    /**
     * @param int $index
     *
     * @return null|array
     */
    private function getReplaceCandidate(
        Tokens $tokens,
        FunctionsAnalyzer $functionAnalyzer,
        $index
    ) {
        if (!$tokens[$index]->isGivenKind(T_STRING)) {
            return null;
        }

        $lowerContent = strtolower($tokens[$index]->getContent());

        if ('get_class' === $lowerContent) {
            return $this->fixGetClassCall($tokens, $functionAnalyzer, $index);
        }

        if (!isset($this->functionsFixMap[$lowerContent])) {
            return null;
        }

        if (!$functionAnalyzer->isGlobalFunctionCall($tokens, $index)) {
            return null;
        }

        // test if function call without parameters
        $braceOpenIndex = $tokens->getNextMeaningfulToken($index);
        if (!$tokens[$braceOpenIndex]->equals('(')) {
            return null;
        }

        $braceCloseIndex = $tokens->getNextMeaningfulToken($braceOpenIndex);
        if (!$tokens[$braceCloseIndex]->equals(')')) {
            return null;
        }

        return $this->getReplacementTokenClones($lowerContent, $braceOpenIndex, $braceCloseIndex);
    }

    /**
     * @param int $index
     *
     * @return null|array
     */
    private function fixGetClassCall(
        Tokens $tokens,
        FunctionsAnalyzer $functionAnalyzer,
        $index
    ) {
        if (!isset($this->functionsFixMap['get_class']) && !isset($this->functionsFixMap['get_class_this'])) {
            return null;
        }

        if (!$functionAnalyzer->isGlobalFunctionCall($tokens, $index)) {
            return null;
        }

        $braceOpenIndex = $tokens->getNextMeaningfulToken($index);
        $braceCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $braceOpenIndex);

        if ($braceCloseIndex === $tokens->getNextMeaningfulToken($braceOpenIndex)) { // no arguments passed
            if (isset($this->functionsFixMap['get_class'])) {
                return $this->getReplacementTokenClones('get_class', $braceOpenIndex, $braceCloseIndex);
            }
        } else {
            if (isset($this->functionsFixMap['get_class_this'])) {
                $isThis = false;

                for ($i = $braceOpenIndex + 1; $i < $braceCloseIndex; ++$i) {
                    if ($tokens[$i]->equalsAny([[T_WHITESPACE], [T_COMMENT], [T_DOC_COMMENT], ')'])) {
                        continue;
                    }

                    if ($tokens[$i]->isGivenKind(T_VARIABLE) && '$this' === strtolower($tokens[$i]->getContent())) {
                        $isThis = true;

                        continue;
                    }

                    if (false === $isThis && $tokens[$i]->equals('(')) {
                        continue;
                    }

                    $isThis = false;

                    break;
                }

                if ($isThis) {
                    return $this->getReplacementTokenClones('get_class_this', $braceOpenIndex, $braceCloseIndex);
                }
            }
        }

        return null;
    }

    /**
     * @param string $lowerContent
     * @param int    $braceOpenIndex
     * @param int    $braceCloseIndex
     *
     * @return array
     */
    private function getReplacementTokenClones($lowerContent, $braceOpenIndex, $braceCloseIndex)
    {
        $clones = [];
        foreach ($this->functionsFixMap[$lowerContent] as $token) {
            $clones[] = clone $token;
        }

        return [
            $braceOpenIndex,
            $braceCloseIndex,
            $clones,
        ];
    }
}
