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

namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Console\Command\HelpCommand;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 */
final class BinaryOperatorSpacesFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @internal
     */
    const SINGLE_SPACE = 'single_space';

    /**
     * @internal
     */
    const NO_SPACE = 'no_space';

    /**
     * @internal
     */
    const ALIGN = 'align';

    /**
     * @internal
     */
    const ALIGN_SINGLE_SPACE = 'align_single_space';

    /**
     * @internal
     */
    const ALIGN_SINGLE_SPACE_MINIMAL = 'align_single_space_minimal';

    /**
     * @internal
     * @const Placeholder used as anchor for right alignment.
     */
    const ALIGN_PLACEHOLDER = "\x2 ALIGNABLE%d \x3";

    /**
     * Keep track of the deepest level ever achieved while
     * parsing the code. Used later to replace alignment
     * placeholders with spaces.
     *
     * @var int
     */
    private $deepestLevel;

    /**
     * Level counter of the current nest level.
     * So one level alignments are not mixed with
     * other level ones.
     *
     * @var int
     */
    private $currentLevel;

    private static $allowedValues = [
        self::ALIGN,
        self::ALIGN_SINGLE_SPACE,
        self::ALIGN_SINGLE_SPACE_MINIMAL,
        self::SINGLE_SPACE,
        self::NO_SPACE,
        null,
    ];

    /**
     * @var string[]
     */
    private static $supportedOperators = [
        '=',
        '*',
        '/',
        '%',
        '<',
        '>',
        '|',
        '^',
        '+',
        '-',
        '&',
        '&=',
        '&&',
        '||',
        '.=',
        '/=',
        '=>',
        '==',
        '>=',
        '===',
        '!=',
        '<>',
        '!==',
        '<=',
        'and',
        'or',
        'xor',
        '-=',
        '%=',
        '*=',
        '|=',
        '+=',
        '<<',
        '<<=',
        '>>',
        '>>=',
        '^=',
        '**',
        '**=',
        '<=>',
        '??',
        '??=',
    ];

    /**
     * @var TokensAnalyzer
     */
    private $tokensAnalyzer;

    /**
     * @var array<string, string>
     */
    private $alignOperatorTokens = [];

    /**
     * @var array<string, string>
     */
    private $operators = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        if (
            null !== $configuration &&
            (\array_key_exists('align_equals', $configuration) || \array_key_exists('align_double_arrow', $configuration))
        ) {
            $configuration = $this->resolveOldConfig($configuration);
        }

        parent::configure($configuration);

        $this->operators = $this->resolveOperatorsFromConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Binary operators should be surrounded by space as configured.',
            [
                new CodeSample(
                    "<?php\n\$a= 1  + \$b^ \$d !==  \$e or   \$f;\n"
                ),
                new CodeSample(
                    '<?php
$aa=  1;
$b=2;

$c = $d    xor    $e;
$f    -=  1;
',
                    ['operators' => ['=' => 'align', 'xor' => null]]
                ),
                new CodeSample(
                    '<?php
$a = $b +=$c;
$d = $ee+=$f;

$g = $b     +=$c;
$h = $ee+=$f;
',
                    ['operators' => ['+=' => 'align_single_space']]
                ),
                new CodeSample(
                    '<?php
$a = $b===$c;
$d = $f   ===  $g;
$h = $i===  $j;
',
                    ['operators' => ['===' => 'align_single_space_minimal']]
                ),
                new CodeSample(
                    '<?php
$foo = \json_encode($bar, JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT);
',
                    ['operators' => ['|' => 'no_space']]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after ArrayIndentationFixer, ArraySyntaxFixer, ListSyntaxFixer, NoMultilineWhitespaceAroundDoubleArrowFixer, PowToExponentiationFixer, StandardizeNotEqualsFixer, StrictComparisonFixer.
     */
    public function getPriority()
    {
        return -31;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $this->tokensAnalyzer = new TokensAnalyzer($tokens);

        // last and first tokens cannot be an operator
        for ($index = $tokens->count() - 2; $index > 0; --$index) {
            if (!$this->tokensAnalyzer->isBinaryOperator($index)) {
                continue;
            }

            if ('=' === $tokens[$index]->getContent()) {
                $isDeclare = $this->isEqualPartOfDeclareStatement($tokens, $index);
                if (false === $isDeclare) {
                    $this->fixWhiteSpaceAroundOperator($tokens, $index);
                } else {
                    $index = $isDeclare; // skip `declare(foo ==bar)`, see `declare_equal_normalize`
                }
            } else {
                $this->fixWhiteSpaceAroundOperator($tokens, $index);
            }

            // previous of binary operator is now never an operator / previous of declare statement cannot be an operator
            --$index;
        }

        if (\count($this->alignOperatorTokens)) {
            $this->fixAlignment($tokens, $this->alignOperatorTokens);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('default', 'Default fix strategy.'))
                ->setDefault(self::SINGLE_SPACE)
                ->setAllowedValues(self::$allowedValues)
                ->getOption(),
            (new FixerOptionBuilder('operators', 'Dictionary of `binary operator` => `fix strategy` values that differ from the default strategy.'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([static function ($option) {
                    foreach ($option as $operator => $value) {
                        if (!\in_array($operator, self::$supportedOperators, true)) {
                            throw new InvalidOptionsException(
                                sprintf(
                                    'Unexpected "operators" key, expected any of "%s", got "%s".',
                                    implode('", "', self::$supportedOperators),
                                    \is_object($operator) ? \get_class($operator) : \gettype($operator).'#'.$operator
                                )
                            );
                        }

                        if (!\in_array($value, self::$allowedValues, true)) {
                            throw new InvalidOptionsException(
                                sprintf(
                                    'Unexpected value for operator "%s", expected any of "%s", got "%s".',
                                    $operator,
                                    implode('", "', self::$allowedValues),
                                    \is_object($value) ? \get_class($value) : (null === $value ? 'null' : \gettype($value).'#'.$value)
                                )
                            );
                        }
                    }

                    return true;
                }])
                ->setDefault([])
                ->getOption(),
            // add deprecated options as BC layer
            (new FixerOptionBuilder('align_double_arrow', 'Whether to apply, remove or ignore double arrows alignment.'))
                ->setDefault(false)
                ->setAllowedValues([true, false, null])
                ->setDeprecationMessage('Use options `operators` and `default` instead.')
                ->getOption(),
            (new FixerOptionBuilder('align_equals', 'Whether to apply, remove or ignore equals alignment.'))
                ->setDefault(false)
                ->setAllowedValues([true, false, null])
                ->setDeprecationMessage('Use options `operators` and `default` instead.')
                ->getOption(),
        ]);
    }

    /**
     * @param int $index
     */
    private function fixWhiteSpaceAroundOperator(Tokens $tokens, $index)
    {
        $tokenContent = strtolower($tokens[$index]->getContent());

        if (!\array_key_exists($tokenContent, $this->operators)) {
            return; // not configured to be changed
        }

        if (self::SINGLE_SPACE === $this->operators[$tokenContent]) {
            $this->fixWhiteSpaceAroundOperatorToSingleSpace($tokens, $index);

            return;
        }

        if (self::NO_SPACE === $this->operators[$tokenContent]) {
            $this->fixWhiteSpaceAroundOperatorToNoSpace($tokens, $index);

            return;
        }

        // schedule for alignment
        $this->alignOperatorTokens[$tokenContent] = $this->operators[$tokenContent];

        if (self::ALIGN === $this->operators[$tokenContent]) {
            return;
        }

        // fix white space after operator
        if ($tokens[$index + 1]->isWhitespace()) {
            if (self::ALIGN_SINGLE_SPACE_MINIMAL === $this->operators[$tokenContent]) {
                $tokens[$index + 1] = new Token([T_WHITESPACE, ' ']);
            }

            return;
        }

        $tokens->insertAt($index + 1, new Token([T_WHITESPACE, ' ']));
    }

    /**
     * @param int $index
     */
    private function fixWhiteSpaceAroundOperatorToSingleSpace(Tokens $tokens, $index)
    {
        // fix white space after operator
        if ($tokens[$index + 1]->isWhitespace()) {
            $content = $tokens[$index + 1]->getContent();
            if (' ' !== $content && false === strpos($content, "\n") && !$tokens[$tokens->getNextNonWhitespace($index + 1)]->isComment()) {
                $tokens[$index + 1] = new Token([T_WHITESPACE, ' ']);
            }
        } else {
            $tokens->insertAt($index + 1, new Token([T_WHITESPACE, ' ']));
        }

        // fix white space before operator
        if ($tokens[$index - 1]->isWhitespace()) {
            $content = $tokens[$index - 1]->getContent();
            if (' ' !== $content && false === strpos($content, "\n") && !$tokens[$tokens->getPrevNonWhitespace($index - 1)]->isComment()) {
                $tokens[$index - 1] = new Token([T_WHITESPACE, ' ']);
            }
        } else {
            $tokens->insertAt($index, new Token([T_WHITESPACE, ' ']));
        }
    }

    /**
     * @param int $index
     */
    private function fixWhiteSpaceAroundOperatorToNoSpace(Tokens $tokens, $index)
    {
        // fix white space after operator
        if ($tokens[$index + 1]->isWhitespace()) {
            $content = $tokens[$index + 1]->getContent();
            if (false === strpos($content, "\n") && !$tokens[$tokens->getNextNonWhitespace($index + 1)]->isComment()) {
                $tokens->clearAt($index + 1);
            }
        }

        // fix white space before operator
        if ($tokens[$index - 1]->isWhitespace()) {
            $content = $tokens[$index - 1]->getContent();
            if (false === strpos($content, "\n") && !$tokens[$tokens->getPrevNonWhitespace($index - 1)]->isComment()) {
                $tokens->clearAt($index - 1);
            }
        }
    }

    /**
     * @param int $index
     *
     * @return false|int index of T_DECLARE where the `=` belongs to or `false`
     */
    private function isEqualPartOfDeclareStatement(Tokens $tokens, $index)
    {
        $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($index);
        if ($tokens[$prevMeaningfulIndex]->isGivenKind(T_STRING)) {
            $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($prevMeaningfulIndex);
            if ($tokens[$prevMeaningfulIndex]->equals('(')) {
                $prevMeaningfulIndex = $tokens->getPrevMeaningfulToken($prevMeaningfulIndex);
                if ($tokens[$prevMeaningfulIndex]->isGivenKind(T_DECLARE)) {
                    return $prevMeaningfulIndex;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function resolveOperatorsFromConfig()
    {
        $operators = [];

        if (null !== $this->configuration['default']) {
            foreach (self::$supportedOperators as $operator) {
                $operators[$operator] = $this->configuration['default'];
            }
        }

        foreach ($this->configuration['operators'] as $operator => $value) {
            if (null === $value) {
                unset($operators[$operator]);
            } else {
                $operators[$operator] = $value;
            }
        }

        if (!\defined('T_SPACESHIP')) {
            unset($operators['<=>']);
        }

        if (!\defined('T_COALESCE')) {
            unset($operators['??']);
        }

        if (!\defined('T_COALESCE_EQUAL')) {
            unset($operators['??=']);
        }

        return $operators;
    }

    /**
     * @return array
     */
    private function resolveOldConfig(array $configuration)
    {
        $newConfig = [
            'operators' => [],
        ];

        foreach ($configuration as $name => $setting) {
            if ('align_double_arrow' === $name) {
                if (true === $configuration[$name]) {
                    $newConfig['operators']['=>'] = self::ALIGN;
                } elseif (false === $configuration[$name]) {
                    $newConfig['operators']['=>'] = self::SINGLE_SPACE;
                } elseif (null !== $configuration[$name]) {
                    throw new InvalidFixerConfigurationException(
                        $this->getName(),
                        sprintf(
                            'Invalid configuration: The option "align_double_arrow" with value %s is invalid. Accepted values are: true, false, null.',
                            $configuration[$name]
                        )
                    );
                }
            } elseif ('align_equals' === $name) {
                if (true === $configuration[$name]) {
                    $newConfig['operators']['='] = self::ALIGN;
                } elseif (false === $configuration[$name]) {
                    $newConfig['operators']['='] = self::SINGLE_SPACE;
                } elseif (null !== $configuration[$name]) {
                    throw new InvalidFixerConfigurationException(
                        $this->getName(),
                        sprintf(
                            'Invalid configuration: The option "align_equals" with value %s is invalid. Accepted values are: true, false, null.',
                            $configuration[$name]
                        )
                    );
                }
            } else {
                throw new InvalidFixerConfigurationException($this->getName(), 'Mixing old configuration with new configuration is not allowed.');
            }
        }

        $message = sprintf(
            'Given configuration is deprecated and will be removed in 3.0. Use configuration %s as replacement for %s.',
            HelpCommand::toString($newConfig),
            HelpCommand::toString($configuration)
        );

        if (getenv('PHP_CS_FIXER_FUTURE_MODE')) {
            throw new InvalidFixerConfigurationException($this->getName(), "{$message} This check was performed as `PHP_CS_FIXER_FUTURE_MODE` env var is set.");
        }

        @trigger_error($message, E_USER_DEPRECATED);

        return $newConfig;
    }

    // Alignment logic related methods

    /**
     * @param array<string, string> $toAlign
     */
    private function fixAlignment(Tokens $tokens, array $toAlign)
    {
        $this->deepestLevel = 0;
        $this->currentLevel = 0;

        foreach ($toAlign as $tokenContent => $alignStrategy) {
            // This fixer works partially on Tokens and partially on string representation of code.
            // During the process of fixing internal state of single Token may be affected by injecting ALIGN_PLACEHOLDER to its content.
            // The placeholder will be resolved by `replacePlaceholders` method by removing placeholder or changing it into spaces.
            // That way of fixing the code causes disturbances in marking Token as changed - if code is perfectly valid then placeholder
            // still be injected and removed, which will cause the `changed` flag to be set.
            // To handle that unwanted behavior we work on clone of Tokens collection and then override original collection with fixed collection.
            $tokensClone = clone $tokens;

            if ('=>' === $tokenContent) {
                $this->injectAlignmentPlaceholdersForArrow($tokensClone, 0, \count($tokens));
            } else {
                $this->injectAlignmentPlaceholders($tokensClone, 0, \count($tokens), $tokenContent);
            }

            // for all tokens that should be aligned but do not have anything to align with, fix spacing if needed
            if (self::ALIGN_SINGLE_SPACE === $alignStrategy || self::ALIGN_SINGLE_SPACE_MINIMAL === $alignStrategy) {
                if ('=>' === $tokenContent) {
                    for ($index = $tokens->count() - 2; $index > 0; --$index) {
                        if ($tokens[$index]->isGivenKind(T_DOUBLE_ARROW)) { // always binary operator, never part of declare statement
                            $this->fixWhiteSpaceBeforeOperator($tokensClone, $index, $alignStrategy);
                        }
                    }
                } elseif ('=' === $tokenContent) {
                    for ($index = $tokens->count() - 2; $index > 0; --$index) {
                        if ('=' === $tokens[$index]->getContent() && !$this->isEqualPartOfDeclareStatement($tokens, $index) && $this->tokensAnalyzer->isBinaryOperator($index)) {
                            $this->fixWhiteSpaceBeforeOperator($tokensClone, $index, $alignStrategy);
                        }
                    }
                } else {
                    for ($index = $tokens->count() - 2; $index > 0; --$index) {
                        $content = $tokens[$index]->getContent();
                        if (strtolower($content) === $tokenContent && $this->tokensAnalyzer->isBinaryOperator($index)) { // never part of declare statement
                            $this->fixWhiteSpaceBeforeOperator($tokensClone, $index, $alignStrategy);
                        }
                    }
                }
            }

            $tokens->setCode($this->replacePlaceholders($tokensClone, $alignStrategy));
        }
    }

    /**
     * @param int    $startAt
     * @param int    $endAt
     * @param string $tokenContent
     */
    private function injectAlignmentPlaceholders(Tokens $tokens, $startAt, $endAt, $tokenContent)
    {
        for ($index = $startAt; $index < $endAt; ++$index) {
            $token = $tokens[$index];

            $content = $token->getContent();
            if (
                strtolower($content) === $tokenContent
                && $this->tokensAnalyzer->isBinaryOperator($index)
                && ('=' !== $content || !$this->isEqualPartOfDeclareStatement($tokens, $index))
            ) {
                $tokens[$index] = new Token(sprintf(self::ALIGN_PLACEHOLDER, $this->deepestLevel).$content);

                continue;
            }

            if ($token->isGivenKind(T_FUNCTION)) {
                ++$this->deepestLevel;

                continue;
            }

            if ($token->equals('(')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

                continue;
            }

            if ($token->equals('[')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $index);

                continue;
            }

            if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index);

                continue;
            }
        }
    }

    /**
     * @param int $startAt
     * @param int $endAt
     */
    private function injectAlignmentPlaceholdersForArrow(Tokens $tokens, $startAt, $endAt)
    {
        for ($index = $startAt; $index < $endAt; ++$index) {
            $token = $tokens[$index];

            if ($token->isGivenKind([T_FOREACH, T_FOR, T_WHILE, T_IF, T_SWITCH])) {
                $index = $tokens->getNextMeaningfulToken($index);
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

                continue;
            }

            if ($token->isGivenKind(T_ARRAY)) { // don't use "$tokens->isArray()" here, short arrays are handled in the next case
                $from = $tokens->getNextMeaningfulToken($index);
                $until = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $from);
                $index = $until;

                $this->injectArrayAlignmentPlaceholders($tokens, $from + 1, $until - 1);

                continue;
            }

            if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $from = $index;
                $until = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $from);
                $index = $until;

                $this->injectArrayAlignmentPlaceholders($tokens, $from + 1, $until - 1);

                continue;
            }

            if ($token->isGivenKind(T_DOUBLE_ARROW)) { // no need to analyze for `isBinaryOperator` (always true), nor if part of declare statement (not valid PHP)
                $tokenContent = sprintf(self::ALIGN_PLACEHOLDER, $this->currentLevel).$token->getContent();

                $nextToken = $tokens[$index + 1];
                if (!$nextToken->isWhitespace()) {
                    $tokenContent .= ' ';
                } elseif ($nextToken->isWhitespace(" \t")) {
                    $tokens[$index + 1] = new Token([T_WHITESPACE, ' ']);
                }

                $tokens[$index] = new Token([T_DOUBLE_ARROW, $tokenContent]);

                continue;
            }

            if ($token->equals(';')) {
                ++$this->deepestLevel;
                ++$this->currentLevel;

                continue;
            }

            if ($token->equals(',')) {
                for ($i = $index; $i < $endAt - 1; ++$i) {
                    if (false !== strpos($tokens[$i - 1]->getContent(), "\n")) {
                        break;
                    }

                    if ($tokens[$i + 1]->isGivenKind([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
                        $arrayStartIndex = $tokens[$i + 1]->isGivenKind(T_ARRAY)
                            ? $tokens->getNextMeaningfulToken($i + 1)
                            : $i + 1
                        ;
                        $blockType = Tokens::detectBlockType($tokens[$arrayStartIndex]);
                        $arrayEndIndex = $tokens->findBlockEnd($blockType['type'], $arrayStartIndex);

                        if ($tokens->isPartialCodeMultiline($arrayStartIndex, $arrayEndIndex)) {
                            break;
                        }
                    }

                    ++$index;
                }
            }
        }
    }

    /**
     * @param int $from
     * @param int $until
     */
    private function injectArrayAlignmentPlaceholders(Tokens $tokens, $from, $until)
    {
        // Only inject placeholders for multi-line arrays
        if ($tokens->isPartialCodeMultiline($from, $until)) {
            ++$this->deepestLevel;
            ++$this->currentLevel;
            $this->injectAlignmentPlaceholdersForArrow($tokens, $from, $until);
            --$this->currentLevel;
        }
    }

    /**
     * @param int    $index
     * @param string $alignStrategy
     */
    private function fixWhiteSpaceBeforeOperator(Tokens $tokens, $index, $alignStrategy)
    {
        // fix white space after operator is not needed as BinaryOperatorSpacesFixer took care of this (if strategy is _not_ ALIGN)
        if (!$tokens[$index - 1]->isWhitespace()) {
            $tokens->insertAt($index, new Token([T_WHITESPACE, ' ']));

            return;
        }

        if (self::ALIGN_SINGLE_SPACE_MINIMAL !== $alignStrategy || $tokens[$tokens->getPrevNonWhitespace($index - 1)]->isComment()) {
            return;
        }

        $content = $tokens[$index - 1]->getContent();
        if (' ' !== $content && false === strpos($content, "\n")) {
            $tokens[$index - 1] = new Token([T_WHITESPACE, ' ']);
        }
    }

    /**
     * Look for group of placeholders and provide vertical alignment.
     *
     * @param string $alignStrategy
     *
     * @return string
     */
    private function replacePlaceholders(Tokens $tokens, $alignStrategy)
    {
        $tmpCode = $tokens->generateCode();

        for ($j = 0; $j <= $this->deepestLevel; ++$j) {
            $placeholder = sprintf(self::ALIGN_PLACEHOLDER, $j);

            if (false === strpos($tmpCode, $placeholder)) {
                continue;
            }

            $lines = explode("\n", $tmpCode);
            $groups = [];
            $groupIndex = 0;
            $groups[$groupIndex] = [];

            foreach ($lines as $index => $line) {
                if (substr_count($line, $placeholder) > 0) {
                    $groups[$groupIndex][] = $index;
                } else {
                    ++$groupIndex;
                    $groups[$groupIndex] = [];
                }
            }

            foreach ($groups as $group) {
                if (\count($group) < 1) {
                    continue;
                }

                if (self::ALIGN !== $alignStrategy) {
                    // move place holders to match strategy
                    foreach ($group as $index) {
                        $currentPosition = strpos($lines[$index], $placeholder);
                        $before = substr($lines[$index], 0, $currentPosition);

                        if (self::ALIGN_SINGLE_SPACE === $alignStrategy) {
                            if (1 > \strlen($before) || ' ' !== substr($before, -1)) { // if last char of before-content is not ' '; add it
                                $before .= ' ';
                            }
                        } elseif (self::ALIGN_SINGLE_SPACE_MINIMAL === $alignStrategy) {
                            if (1 !== Preg::match('/^\h+$/', $before)) { // if indent; do not move, leave to other fixer
                                $before = rtrim($before).' ';
                            }
                        }

                        $lines[$index] = $before.substr($lines[$index], $currentPosition);
                    }
                }

                $rightmostSymbol = 0;
                foreach ($group as $index) {
                    $rightmostSymbol = max($rightmostSymbol, strpos(utf8_decode($lines[$index]), $placeholder));
                }

                foreach ($group as $index) {
                    $line = $lines[$index];
                    $currentSymbol = strpos(utf8_decode($line), $placeholder);
                    $delta = abs($rightmostSymbol - $currentSymbol);

                    if ($delta > 0) {
                        $line = str_replace($placeholder, str_repeat(' ', $delta).$placeholder, $line);
                        $lines[$index] = $line;
                    }
                }
            }

            $tmpCode = str_replace($placeholder, '', implode("\n", $lines));
        }

        return $tmpCode;
    }
}
