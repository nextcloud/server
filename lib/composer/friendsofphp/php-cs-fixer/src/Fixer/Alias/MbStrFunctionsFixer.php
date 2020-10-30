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

namespace PhpCsFixer\Fixer\Alias;

use PhpCsFixer\AbstractFunctionReferenceFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class MbStrFunctionsFixer extends AbstractFunctionReferenceFixer
{
    /**
     * @var array the list of the string-related function names and their mb_ equivalent
     */
    private static $functionsMap = [
        'str_split' => ['alternativeName' => 'mb_str_split', 'argumentCount' => [1, 2, 3]],
        'stripos' => ['alternativeName' => 'mb_stripos', 'argumentCount' => [2, 3]],
        'stristr' => ['alternativeName' => 'mb_stristr', 'argumentCount' => [2, 3]],
        'strlen' => ['alternativeName' => 'mb_strlen', 'argumentCount' => [1]],
        'strpos' => ['alternativeName' => 'mb_strpos', 'argumentCount' => [2, 3]],
        'strrchr' => ['alternativeName' => 'mb_strrchr', 'argumentCount' => [2]],
        'strripos' => ['alternativeName' => 'mb_strripos', 'argumentCount' => [2, 3]],
        'strrpos' => ['alternativeName' => 'mb_strrpos', 'argumentCount' => [2, 3]],
        'strstr' => ['alternativeName' => 'mb_strstr', 'argumentCount' => [2, 3]],
        'strtolower' => ['alternativeName' => 'mb_strtolower', 'argumentCount' => [1]],
        'strtoupper' => ['alternativeName' => 'mb_strtoupper', 'argumentCount' => [1]],
        'substr' => ['alternativeName' => 'mb_substr', 'argumentCount' => [2, 3]],
        'substr_count' => ['alternativeName' => 'mb_substr_count', 'argumentCount' => [2, 3, 4]],
    ];

    /**
     * @var array<string, array>
     */
    private $functions;

    public function __construct()
    {
        parent::__construct();

        $this->functions = array_filter(
            self::$functionsMap,
            static function (array $mapping) {
                return \function_exists($mapping['alternativeName']) && (new \ReflectionFunction($mapping['alternativeName']))->isInternal();
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replace non multibyte-safe functions with corresponding mb function.',
            [
                new CodeSample(
                    '<?php
$a = strlen($a);
$a = strpos($a, $b);
$a = strrpos($a, $b);
$a = substr($a, $b);
$a = strtolower($a);
$a = strtoupper($a);
$a = stripos($a, $b);
$a = strripos($a, $b);
$a = strstr($a, $b);
$a = stristr($a, $b);
$a = strrchr($a, $b);
$a = substr_count($a, $b);
'
                ),
            ],
            null,
            'Risky when any of the functions are overridden.'
        );
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
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();
        foreach ($this->functions as $functionIdentity => $functionReplacement) {
            $currIndex = 0;
            while (null !== $currIndex) {
                // try getting function reference and translate boundaries for humans
                $boundaries = $this->find($functionIdentity, $tokens, $currIndex, $tokens->count() - 1);
                if (null === $boundaries) {
                    // next function search, as current one not found
                    continue 2;
                }

                list($functionName, $openParenthesis, $closeParenthesis) = $boundaries;
                $count = $argumentsAnalyzer->countArguments($tokens, $openParenthesis, $closeParenthesis);
                if (!\in_array($count, $functionReplacement['argumentCount'], true)) {
                    continue 2;
                }

                // analysing cursor shift, so nested calls could be processed
                $currIndex = $openParenthesis;

                $tokens[$functionName] = new Token([T_STRING, $functionReplacement['alternativeName']]);
            }
        }
    }
}
