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

namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Graham Campbell <graham@alt-three.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocAlignFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * @internal
     */
    const ALIGN_LEFT = 'left';

    /**
     * @internal
     */
    const ALIGN_VERTICAL = 'vertical';

    /**
     * @var string
     */
    private $regex;

    /**
     * @var string
     */
    private $regexCommentLine;

    /**
     * @var string
     */
    private $align;

    private static $alignableTags = [
        'param',
        'property',
        'property-read',
        'property-write',
        'return',
        'throws',
        'type',
        'var',
        'method',
    ];

    private static $tagsWithName = [
        'param',
        'property',
    ];

    private static $tagsWithMethodSignature = [
        'method',
    ];

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $tagsWithNameToAlign = array_intersect($this->configuration['tags'], self::$tagsWithName);
        $tagsWithMethodSignatureToAlign = array_intersect($this->configuration['tags'], self::$tagsWithMethodSignature);
        $tagsWithoutNameToAlign = array_diff($this->configuration['tags'], $tagsWithNameToAlign, $tagsWithMethodSignatureToAlign);
        $types = [];

        $indent = '(?P<indent>(?: {2}|\t)*)';
        // e.g. @param <hint> <$var>
        if (!empty($tagsWithNameToAlign)) {
            $types[] = '(?P<tag>'.implode('|', $tagsWithNameToAlign).')\s+(?P<hint>[^$]+?)\s+(?P<var>(?:&|\.{3})?\$[^\s]+)';
        }

        // e.g. @return <hint>
        if (!empty($tagsWithoutNameToAlign)) {
            $types[] = '(?P<tag2>'.implode('|', $tagsWithoutNameToAlign).')\s+(?P<hint2>[^\s]+?)';
        }

        // e.g. @method <hint> <signature>
        if (!empty($tagsWithMethodSignatureToAlign)) {
            $types[] = '(?P<tag3>'.implode('|', $tagsWithMethodSignatureToAlign).')(\s+(?P<hint3>[^\s(]+)|)\s+(?P<signature>.+\))';
        }

        // optional <desc>
        $desc = '(?:\s+(?P<desc>\V*))';

        $this->regex = '/^'.$indent.' \* @(?:'.implode('|', $types).')'.$desc.'\s*$/u';
        $this->regexCommentLine = '/^'.$indent.' \*(?! @)(?:\s+(?P<desc>\V+))(?<!\*\/)\r?$/u';
        $this->align = $this->configuration['align'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $code = <<<'EOF'
<?php
/**
 * @param  EngineInterface $templating
 * @param string      $format
 * @param  int  $code       an HTTP response status code
 * @param    bool         $debug
 * @param  mixed    &$reference     a parameter passed by reference
 */

EOF;

        return new FixerDefinition(
            'All items of the given phpdoc tags must be either left-aligned or (by default) aligned vertically.',
            [
                new CodeSample($code),
                new CodeSample($code, ['align' => self::ALIGN_VERTICAL]),
                new CodeSample($code, ['align' => self::ALIGN_LEFT]),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after CommentToPhpdocFixer, CommentToPhpdocFixer, GeneralPhpdocAnnotationRemoveFixer, NoBlankLinesAfterPhpdocFixer, NoEmptyPhpdocFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAddMissingParamAnnotationFixer, PhpdocAddMissingParamAnnotationFixer, PhpdocAnnotationWithoutDotFixer, PhpdocIndentFixer, PhpdocIndentFixer, PhpdocInlineTagFixer, PhpdocLineSpanFixer, PhpdocNoAccessFixer, PhpdocNoAliasTagFixer, PhpdocNoEmptyReturnFixer, PhpdocNoPackageFixer, PhpdocNoUselessInheritdocFixer, PhpdocOrderFixer, PhpdocReturnSelfReferenceFixer, PhpdocScalarFixer, PhpdocScalarFixer, PhpdocSeparationFixer, PhpdocSingleLineVarSpacingFixer, PhpdocSummaryFixer, PhpdocToCommentFixer, PhpdocToCommentFixer, PhpdocToParamTypeFixer, PhpdocToReturnTypeFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer, PhpdocTrimFixer, PhpdocTypesFixer, PhpdocTypesFixer, PhpdocTypesOrderFixer, PhpdocVarAnnotationCorrectOrderFixer, PhpdocVarWithoutNameFixer.
     */
    public function getPriority()
    {
        /*
         * Should be run after all other docblock fixers. This because they
         * modify other annotations to change their type and or separation
         * which totally change the behavior of this fixer. It's important that
         * annotations are of the correct type, and are grouped correctly
         * before running this fixer.
         */
        return -21;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $content = $token->getContent();
            $docBlock = new DocBlock($content);
            $this->fixDocBlock($docBlock);
            $newContent = $docBlock->getContent();
            if ($newContent !== $content) {
                $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $tags = new FixerOptionBuilder('tags', 'The tags that should be aligned.');
        $tags
            ->setAllowedTypes(['array'])
            ->setAllowedValues([new AllowedValueSubset(self::$alignableTags)])
            /*
             * By default, all tags apart from @property and @method will be aligned for backwards compatibility
             * @TODO 3.0 Align all available tags by default
             */
            ->setDefault([
                'param',
                'return',
                'throws',
                'type',
                'var',
            ])
        ;

        $align = new FixerOptionBuilder('align', 'Align comments');
        $align
            ->setAllowedTypes(['string'])
            ->setAllowedValues([self::ALIGN_LEFT, self::ALIGN_VERTICAL])
            ->setDefault(self::ALIGN_VERTICAL)
        ;

        return new FixerConfigurationResolver([$tags->getOption(), $align->getOption()]);
    }

    private function fixDocBlock(DocBlock $docBlock)
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        for ($i = 0, $l = \count($docBlock->getLines()); $i < $l; ++$i) {
            $items = [];
            $matches = $this->getMatches($docBlock->getLine($i)->getContent());

            if (null === $matches) {
                continue;
            }

            $current = $i;
            $items[] = $matches;

            while (true) {
                if (null === $docBlock->getLine(++$i)) {
                    break 2;
                }

                $matches = $this->getMatches($docBlock->getLine($i)->getContent(), true);
                if (null === $matches) {
                    break;
                }

                $items[] = $matches;
            }

            // compute the max length of the tag, hint and variables
            $tagMax = 0;
            $hintMax = 0;
            $varMax = 0;

            foreach ($items as $item) {
                if (null === $item['tag']) {
                    continue;
                }

                $tagMax = max($tagMax, \strlen($item['tag']));
                $hintMax = max($hintMax, \strlen($item['hint']));
                $varMax = max($varMax, \strlen($item['var']));
            }

            $currTag = null;

            // update
            foreach ($items as $j => $item) {
                if (null === $item['tag']) {
                    if ('@' === $item['desc'][0]) {
                        $docBlock->getLine($current + $j)->setContent($item['indent'].' * '.$item['desc'].$lineEnding);

                        continue;
                    }

                    $extraIndent = 2;

                    if (\in_array($currTag, self::$tagsWithName, true) || \in_array($currTag, self::$tagsWithMethodSignature, true)) {
                        $extraIndent = 3;
                    }

                    $line =
                        $item['indent']
                        .' *  '
                        .$this->getIndent(
                            $tagMax + $hintMax + $varMax + $extraIndent,
                            $this->getLeftAlignedDescriptionIndent($items, $j)
                        )
                        .$item['desc']
                        .$lineEnding;

                    $docBlock->getLine($current + $j)->setContent($line);

                    continue;
                }

                $currTag = $item['tag'];

                $line =
                    $item['indent']
                    .' * @'
                    .$item['tag']
                    .$this->getIndent(
                        $tagMax - \strlen($item['tag']) + 1,
                        $item['hint'] ? 1 : 0
                    )
                    .$item['hint']
                ;

                if (!empty($item['var'])) {
                    $line .=
                        $this->getIndent(($hintMax ?: -1) - \strlen($item['hint']) + 1)
                        .$item['var']
                        .(
                            !empty($item['desc'])
                            ? $this->getIndent($varMax - \strlen($item['var']) + 1).$item['desc'].$lineEnding
                            : $lineEnding
                        )
                    ;
                } elseif (!empty($item['desc'])) {
                    $line .= $this->getIndent($hintMax - \strlen($item['hint']) + 1).$item['desc'].$lineEnding;
                } else {
                    $line .= $lineEnding;
                }

                $docBlock->getLine($current + $j)->setContent($line);
            }
        }
    }

    /**
     * @param string $line
     * @param bool   $matchCommentOnly
     *
     * @return null|array<string, null|string>
     */
    private function getMatches($line, $matchCommentOnly = false)
    {
        if (Preg::match($this->regex, $line, $matches)) {
            if (!empty($matches['tag2'])) {
                $matches['tag'] = $matches['tag2'];
                $matches['hint'] = $matches['hint2'];
                $matches['var'] = '';
            }

            if (!empty($matches['tag3'])) {
                $matches['tag'] = $matches['tag3'];
                $matches['hint'] = $matches['hint3'];
                $matches['var'] = $matches['signature'];
            }

            if (isset($matches['hint'])) {
                $matches['hint'] = trim($matches['hint']);
            }

            return $matches;
        }

        if ($matchCommentOnly && Preg::match($this->regexCommentLine, $line, $matches)) {
            $matches['tag'] = null;
            $matches['var'] = '';
            $matches['hint'] = '';

            return $matches;
        }

        return null;
    }

    /**
     * @param int $verticalAlignIndent
     * @param int $leftAlignIndent
     *
     * @return string
     */
    private function getIndent($verticalAlignIndent, $leftAlignIndent = 1)
    {
        $indent = self::ALIGN_VERTICAL === $this->align ? $verticalAlignIndent : $leftAlignIndent;

        return str_repeat(' ', $indent);
    }

    /**
     * @param array[] $items
     * @param int     $index
     *
     * @return int
     */
    private function getLeftAlignedDescriptionIndent(array $items, $index)
    {
        if (self::ALIGN_LEFT !== $this->align) {
            return 0;
        }

        // Find last tagged line:
        $item = null;
        for (; $index >= 0; --$index) {
            $item = $items[$index];
            if (null !== $item['tag']) {
                break;
            }
        }

        // No last tag found — no indent:
        if (null === $item) {
            return 0;
        }

        // Indent according to existing values:
        return
            $this->getSentenceIndent($item['tag']) +
            $this->getSentenceIndent($item['hint']) +
            $this->getSentenceIndent($item['var']);
    }

    /**
     * Get indent for sentence.
     *
     * @param null|string $sentence
     *
     * @return int
     */
    private function getSentenceIndent($sentence)
    {
        if (null === $sentence) {
            return 0;
        }

        $length = \strlen($sentence);

        return 0 === $length ? 0 : $length + 1;
    }
}
