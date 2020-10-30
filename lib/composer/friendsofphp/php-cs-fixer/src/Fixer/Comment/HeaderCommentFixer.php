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

namespace PhpCsFixer\Fixer\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\AliasedFixerOptionBuilder;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symfony\Component\OptionsResolver\Options;

/**
 * @author Antonio J. García Lagar <aj@garcialagar.es>
 * @author SpacePossum
 */
final class HeaderCommentFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    const HEADER_PHPDOC = 'PHPDoc';
    const HEADER_COMMENT = 'comment';

    /** @deprecated will be removed in 3.0 */
    const HEADER_LOCATION_AFTER_OPEN = 1;
    /** @deprecated will be removed in 3.0 */
    const HEADER_LOCATION_AFTER_DECLARE_STRICT = 2;

    /** @deprecated will be removed in 3.0 */
    const HEADER_LINE_SEPARATION_BOTH = 1;
    /** @deprecated will be removed in 3.0 */
    const HEADER_LINE_SEPARATION_TOP = 2;
    /** @deprecated will be removed in 3.0 */
    const HEADER_LINE_SEPARATION_BOTTOM = 3;
    /** @deprecated will be removed in 3.0 */
    const HEADER_LINE_SEPARATION_NONE = 4;

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Add, replace or remove header comment.',
            [
                new CodeSample(
                    '<?php
declare(strict_types=1);

namespace A\B;

echo 1;
',
                    [
                        'header' => 'Made with love.',
                    ]
                ),
                new CodeSample(
                    '<?php
declare(strict_types=1);

namespace A\B;

echo 1;
',
                    [
                        'header' => 'Made with love.',
                        'comment_type' => 'PHPDoc',
                        'location' => 'after_open',
                        'separate' => 'bottom',
                    ]
                ),
                new CodeSample(
                    '<?php
declare(strict_types=1);

namespace A\B;

echo 1;
',
                    [
                        'header' => 'Made with love.',
                        'comment_type' => 'comment',
                        'location' => 'after_declare_strict',
                    ]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return isset($tokens[0]) && $tokens[0]->isGivenKind(T_OPEN_TAG) && $tokens->isMonolithicPhp();
    }

    /**
     * {@inheritdoc}
     *
     * Must run after DeclareStrictTypesFixer, NoBlankLinesAfterPhpdocFixer.
     */
    public function getPriority()
    {
        // When this fixer is configured with ["separate" => "bottom", "comment_type" => "PHPDoc"]
        // and the target file has no namespace or declare() construct,
        // the fixed header comment gets trimmed by NoBlankLinesAfterPhpdocFixer if we run before it.
        return -30;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $location = $this->configuration['location'];

        $locationIndexes = [];
        foreach (['after_open', 'after_declare_strict'] as $possibleLocation) {
            $locationIndex = $this->findHeaderCommentInsertionIndex($tokens, $possibleLocation);

            if (!isset($locationIndexes[$locationIndex]) || $possibleLocation === $location) {
                $locationIndexes[$locationIndex] = $possibleLocation;

                continue;
            }
        }

        foreach (array_values($locationIndexes) as $possibleLocation) {
            // figure out where the comment should be placed
            $headerNewIndex = $this->findHeaderCommentInsertionIndex($tokens, $possibleLocation);

            // check if there is already a comment
            $headerCurrentIndex = $this->findHeaderCommentCurrentIndex($tokens, $headerNewIndex - 1);

            if (null === $headerCurrentIndex) {
                if ('' === $this->configuration['header'] || $possibleLocation !== $location) {
                    continue;
                }

                $this->insertHeader($tokens, $headerNewIndex);
            } elseif ($this->getHeaderAsComment() !== $tokens[$headerCurrentIndex]->getContent() || $possibleLocation !== $location) {
                $this->removeHeader($tokens, $headerCurrentIndex);

                if ('' === $this->configuration['header']) {
                    continue;
                }

                if ($possibleLocation === $location) {
                    $this->insertHeader($tokens, $headerNewIndex);
                }
            } else {
                $this->fixWhiteSpaceAroundHeader($tokens, $headerCurrentIndex);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $fixerName = $this->getName();

        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('header', 'Proper header content.'))
                ->setAllowedTypes(['string'])
                ->setNormalizer(static function (Options $options, $value) use ($fixerName) {
                    if ('' === trim($value)) {
                        return '';
                    }

                    if (false !== strpos($value, '*/')) {
                        throw new InvalidFixerConfigurationException($fixerName, 'Cannot use \'*/\' in header.');
                    }

                    return $value;
                })
                ->getOption(),
            (new AliasedFixerOptionBuilder(
                new FixerOptionBuilder('comment_type', 'Comment syntax type.'),
                'commentType'
            ))
                ->setAllowedValues([self::HEADER_PHPDOC, self::HEADER_COMMENT])
                ->setDefault(self::HEADER_COMMENT)
                ->getOption(),
            (new FixerOptionBuilder('location', 'The location of the inserted header.'))
                ->setAllowedValues(['after_open', 'after_declare_strict'])
                ->setDefault('after_declare_strict')
                ->getOption(),
            (new FixerOptionBuilder('separate', 'Whether the header should be separated from the file content with a new line.'))
                ->setAllowedValues(['both', 'top', 'bottom', 'none'])
                ->setDefault('both')
                ->getOption(),
        ]);
    }

    /**
     * Enclose the given text in a comment block.
     *
     * @return string
     */
    private function getHeaderAsComment()
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        $comment = (self::HEADER_COMMENT === $this->configuration['comment_type'] ? '/*' : '/**').$lineEnding;
        $lines = explode("\n", str_replace("\r", '', $this->configuration['header']));

        foreach ($lines as $line) {
            $comment .= rtrim(' * '.$line).$lineEnding;
        }

        return $comment.' */';
    }

    /**
     * @param int $headerNewIndex
     *
     * @return null|int
     */
    private function findHeaderCommentCurrentIndex(Tokens $tokens, $headerNewIndex)
    {
        $index = $tokens->getNextNonWhitespace($headerNewIndex);

        if (null === $index || !$tokens[$index]->isComment()) {
            return null;
        }

        $next = $index + 1;

        if (!isset($tokens[$next]) || \in_array($this->configuration['separate'], ['top', 'none'], true) || !$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
            return $index;
        }

        if ($tokens[$next]->isWhitespace()) {
            if (!Preg::match('/^\h*\R\h*$/D', $tokens[$next]->getContent())) {
                return $index;
            }

            ++$next;
        }

        if (!isset($tokens[$next]) || !$tokens[$next]->isClassy() && !$tokens[$next]->isGivenKind(T_FUNCTION)) {
            return $index;
        }

        return $this->getHeaderAsComment() === $tokens[$index]->getContent() ? $index : null;
    }

    /**
     * Find the index where the header comment must be inserted.
     *
     * @param string $location
     *
     * @return int
     */
    private function findHeaderCommentInsertionIndex(Tokens $tokens, $location)
    {
        if ('after_open' === $location) {
            return 1;
        }

        $index = $tokens->getNextMeaningfulToken(0);
        if (null === $index) {
            // file without meaningful tokens but an open tag, comment should always be placed directly after the open tag
            return 1;
        }

        if (!$tokens[$index]->isGivenKind(T_DECLARE)) {
            return 1;
        }

        $next = $tokens->getNextMeaningfulToken($index);
        if (null === $next || !$tokens[$next]->equals('(')) {
            return 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if (null === $next || !$tokens[$next]->equals([T_STRING, 'strict_types'], false)) {
            return 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if (null === $next || !$tokens[$next]->equals('=')) {
            return 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if (null === $next || !$tokens[$next]->isGivenKind(T_LNUMBER)) {
            return 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if (null === $next || !$tokens[$next]->equals(')')) {
            return 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if (null === $next || !$tokens[$next]->equals(';')) { // don't insert after close tag
            return 1;
        }

        return $next + 1;
    }

    /**
     * @param int $headerIndex
     */
    private function fixWhiteSpaceAroundHeader(Tokens $tokens, $headerIndex)
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        // fix lines after header comment
        if (
            ('both' === $this->configuration['separate'] || 'bottom' === $this->configuration['separate'])
            && null !== $tokens->getNextMeaningfulToken($headerIndex)
        ) {
            $expectedLineCount = 2;
        } else {
            $expectedLineCount = 1;
        }
        if ($headerIndex === \count($tokens) - 1) {
            $tokens->insertAt($headerIndex + 1, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount)]));
        } else {
            $lineBreakCount = $this->getLineBreakCount($tokens, $headerIndex, 1);
            if ($lineBreakCount < $expectedLineCount) {
                $missing = str_repeat($lineEnding, $expectedLineCount - $lineBreakCount);
                if ($tokens[$headerIndex + 1]->isWhitespace()) {
                    $tokens[$headerIndex + 1] = new Token([T_WHITESPACE, $missing.$tokens[$headerIndex + 1]->getContent()]);
                } else {
                    $tokens->insertAt($headerIndex + 1, new Token([T_WHITESPACE, $missing]));
                }
            } elseif ($lineBreakCount > $expectedLineCount && $tokens[$headerIndex + 1]->isWhitespace()) {
                $newLinesToRemove = $lineBreakCount - $expectedLineCount;
                $tokens[$headerIndex + 1] = new Token([
                    T_WHITESPACE,
                    Preg::replace("/^\\R{{$newLinesToRemove}}/", '', $tokens[$headerIndex + 1]->getContent()),
                ]);
            }
        }

        // fix lines before header comment
        $expectedLineCount = 'both' === $this->configuration['separate'] || 'top' === $this->configuration['separate'] ? 2 : 1;
        $prev = $tokens->getPrevNonWhitespace($headerIndex);

        $regex = '/\h$/';
        if ($tokens[$prev]->isGivenKind(T_OPEN_TAG) && Preg::match($regex, $tokens[$prev]->getContent())) {
            $tokens[$prev] = new Token([T_OPEN_TAG, Preg::replace($regex, $lineEnding, $tokens[$prev]->getContent())]);
        }

        $lineBreakCount = $this->getLineBreakCount($tokens, $headerIndex, -1);
        if ($lineBreakCount < $expectedLineCount) {
            // because of the way the insert index was determined for header comment there cannot be an empty token here
            $tokens->insertAt($headerIndex, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount - $lineBreakCount)]));
        }
    }

    /**
     * @param int $index
     * @param int $direction
     *
     * @return int
     */
    private function getLineBreakCount(Tokens $tokens, $index, $direction)
    {
        $whitespace = '';

        for ($index += $direction; isset($tokens[$index]); $index += $direction) {
            $token = $tokens[$index];

            if ($token->isWhitespace()) {
                $whitespace .= $token->getContent();

                continue;
            }

            if (-1 === $direction && $token->isGivenKind(T_OPEN_TAG)) {
                $whitespace .= $token->getContent();
            }

            if ('' !== $token->getContent()) {
                break;
            }
        }

        return substr_count($whitespace, "\n");
    }

    private function removeHeader(Tokens $tokens, $index)
    {
        $prevIndex = $index - 1;
        $prevToken = $tokens[$prevIndex];
        $newlineRemoved = false;

        if ($prevToken->isWhitespace()) {
            $content = $prevToken->getContent();

            if (Preg::match('/\R/', $content)) {
                $newlineRemoved = true;
            }

            $content = Preg::replace('/\R?\h*$/', '', $content);

            if ('' !== $content) {
                $tokens[$prevIndex] = new Token([T_WHITESPACE, $content]);
            } else {
                $tokens->clearAt($prevIndex);
            }
        }

        $nextIndex = $index + 1;
        $nextToken = isset($tokens[$nextIndex]) ? $tokens[$nextIndex] : null;

        if (!$newlineRemoved && null !== $nextToken && $nextToken->isWhitespace()) {
            $content = Preg::replace('/^\R/', '', $nextToken->getContent());

            if ('' !== $content) {
                $tokens[$nextIndex] = new Token([T_WHITESPACE, $content]);
            } else {
                $tokens->clearAt($nextIndex);
            }
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    /**
     * @param int $index
     */
    private function insertHeader(Tokens $tokens, $index)
    {
        $tokens->insertAt($index, new Token([self::HEADER_COMMENT === $this->configuration['comment_type'] ? T_COMMENT : T_DOC_COMMENT, $this->getHeaderAsComment()]));

        $this->fixWhiteSpaceAroundHeader($tokens, $index);
    }
}
