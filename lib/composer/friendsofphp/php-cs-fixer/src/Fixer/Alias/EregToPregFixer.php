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

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\PregException;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;

/**
 * @author Matteo Beccati <matteo@beccati.com>
 */
final class EregToPregFixer extends AbstractFixer
{
    /**
     * @var array the list of the ext/ereg function names, their preg equivalent and the preg modifier(s), if any
     *            all condensed in an array of arrays
     */
    private static $functions = [
        ['ereg', 'preg_match', ''],
        ['eregi', 'preg_match', 'i'],
        ['ereg_replace', 'preg_replace', ''],
        ['eregi_replace', 'preg_replace', 'i'],
        ['split', 'preg_split', ''],
        ['spliti', 'preg_split', 'i'],
    ];

    /**
     * @var array the list of preg delimiters, in order of preference
     */
    private static $delimiters = ['/', '#', '!'];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replace deprecated `ereg` regular expression functions with `preg`.',
            [new CodeSample("<?php \$x = ereg('[A-Z]');\n")],
            null,
            'Risky if the `ereg` function is overridden.'
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
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $end = $tokens->count() - 1;
        $functionsAnalyzer = new FunctionsAnalyzer();

        foreach (self::$functions as $map) {
            // the sequence is the function name, followed by "(" and a quoted string
            $seq = [[T_STRING, $map[0]], '(', [T_CONSTANT_ENCAPSED_STRING]];

            $currIndex = 0;
            while (null !== $currIndex) {
                $match = $tokens->findSequence($seq, $currIndex, $end, false);

                // did we find a match?
                if (null === $match) {
                    break;
                }

                // findSequence also returns the tokens, but we're only interested in the indexes, i.e.:
                // 0 => function name,
                // 1 => bracket "("
                // 2 => quoted string passed as 1st parameter
                $match = array_keys($match);

                // advance tokenizer cursor
                $currIndex = $match[2];

                if (!$functionsAnalyzer->isGlobalFunctionCall($tokens, $match[0])) {
                    continue;
                }

                // ensure the first parameter is just a string (e.g. has nothing appended)
                $next = $tokens->getNextMeaningfulToken($match[2]);
                if (null === $next || !$tokens[$next]->equalsAny([',', ')'])) {
                    continue;
                }

                // convert to PCRE
                $regexTokenContent = $tokens[$match[2]]->getContent();
                $string = substr($regexTokenContent, 1, -1);
                $quote = $regexTokenContent[0];
                $delim = $this->getBestDelimiter($string);
                $preg = $delim.addcslashes($string, $delim).$delim.'D'.$map[2];

                // check if the preg is valid
                if (!$this->checkPreg($preg)) {
                    continue;
                }

                // modify function and argument
                $tokens[$match[0]] = new Token([T_STRING, $map[1]]);
                $tokens[$match[2]] = new Token([T_CONSTANT_ENCAPSED_STRING, $quote.$preg.$quote]);
            }
        }
    }

    /**
     * Check the validity of a PCRE.
     *
     * @param string $pattern the regular expression
     *
     * @return bool
     */
    private function checkPreg($pattern)
    {
        try {
            Preg::match($pattern, '');

            return true;
        } catch (PregException $e) {
            return false;
        }
    }

    /**
     * Get the delimiter that would require the least escaping in a regular expression.
     *
     * @param string $pattern the regular expression
     *
     * @return string the preg delimiter
     */
    private function getBestDelimiter($pattern)
    {
        // try do find something that's not used
        $delimiters = [];
        foreach (self::$delimiters as $k => $d) {
            if (false === strpos($pattern, $d)) {
                return $d;
            }

            $delimiters[$d] = [substr_count($pattern, $d), $k];
        }

        // return the least used delimiter, using the position in the list as a tie breaker
        uasort($delimiters, static function ($a, $b) {
            if ($a[0] === $b[0]) {
                return Utils::cmpInt($a, $b);
            }

            return $a[0] < $b[0] ? -1 : 1;
        });

        return key($delimiters);
    }
}
