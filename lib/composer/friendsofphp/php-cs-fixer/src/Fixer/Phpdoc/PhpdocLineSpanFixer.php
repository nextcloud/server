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
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Gert de Pagter <BackEndTea@gmail.com>
 */
final class PhpdocLineSpanFixer extends AbstractFixer implements WhitespacesAwareFixerInterface, ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Changes doc blocks from single to multi line, or reversed. Works for class constants, properties and methods only.',
            [
                new CodeSample("<?php\n\nclass Foo{\n    /** @var bool */\n    public \$var;\n}\n"),
                new CodeSample(
                    "<?php\n\nclass Foo{\n    /**\n    * @var bool\n    */\n    public \$var;\n}\n",
                    ['property' => 'single']
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before PhpdocAlignFixer.
     * Must run after CommentToPhpdocFixer, GeneralPhpdocAnnotationRemoveFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
     */
    public function getPriority()
    {
        return 0;
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
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('const', 'Whether const blocks should be single or multi line'))
                ->setAllowedValues(['single', 'multi'])
                ->setDefault('multi')
                ->getOption(),
            (new FixerOptionBuilder('property', 'Whether property doc blocks should be single or multi line'))
                ->setAllowedValues(['single', 'multi'])
                ->setDefault('multi')
                ->getOption(),
            (new FixerOptionBuilder('method', 'Whether method doc blocks should be single or multi line'))
                ->setAllowedValues(['single', 'multi'])
                ->setDefault('multi')
                ->getOption(),
        ]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $analyzer = new TokensAnalyzer($tokens);

        $elements = $analyzer->getClassyElements();

        foreach ($elements as $index => $element) {
            if (!$this->hasDocBlock($tokens, $index)) {
                continue;
            }

            $type = $element['type'];
            $docIndex = $this->getDocBlockIndex($tokens, $index);
            $doc = new DocBlock($tokens[$docIndex]->getContent());

            if ('multi' === $this->configuration[$type]) {
                $doc->makeMultiLine($originalIndent = $this->detectIndent($tokens, $docIndex), $this->whitespacesConfig->getLineEnding());
            } else {
                $doc->makeSingleLine();
            }

            $tokens->offsetSet($docIndex, new Token([T_DOC_COMMENT, $doc->getContent()]));
        }
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function hasDocBlock(Tokens $tokens, $index)
    {
        $docBlockIndex = $this->getDocBlockIndex($tokens, $index);

        return $tokens[$docBlockIndex]->isGivenKind(T_DOC_COMMENT);
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function getDocBlockIndex(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([
            T_PUBLIC,
            T_PROTECTED,
            T_PRIVATE,
            T_FINAL,
            T_ABSTRACT,
            T_COMMENT,
            T_VAR,
            T_STATIC,
            T_STRING,
            T_NS_SEPARATOR,
            CT::T_NULLABLE_TYPE,
        ]));

        return $index;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function detectIndent(Tokens $tokens, $index)
    {
        if (!$tokens[$index - 1]->isWhitespace()) {
            return ''; // cannot detect indent
        }

        $explodedContent = explode($this->whitespacesConfig->getLineEnding(), $tokens[$index - 1]->getContent());

        return end($explodedContent);
    }
}
