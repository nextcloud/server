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

namespace PhpCsFixer\Fixer\Strict;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author SpacePossum
 */
final class DeclareStrictTypesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Force strict types declaration in all files. Requires PHP >= 7.0.',
            [
                new VersionSpecificCodeSample(
                    "<?php\n",
                    new VersionSpecification(70000)
                ),
            ],
            null,
            'Forcing strict types will stop non strict code from working.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before BlankLineAfterOpeningTagFixer, DeclareEqualNormalizeFixer, HeaderCommentFixer.
     */
    public function getPriority()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return \PHP_VERSION_ID >= 70000 && isset($tokens[0]) && $tokens[0]->isGivenKind(T_OPEN_TAG);
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
        // check if the declaration is already done
        $searchIndex = $tokens->getNextMeaningfulToken(0);
        if (null === $searchIndex) {
            $this->insertSequence($tokens); // declaration not found, insert one

            return;
        }

        $sequenceLocation = $tokens->findSequence([[T_DECLARE, 'declare'], '(', [T_STRING, 'strict_types'], '=', [T_LNUMBER], ')'], $searchIndex, null, false);
        if (null === $sequenceLocation) {
            $this->insertSequence($tokens); // declaration not found, insert one

            return;
        }

        $this->fixStrictTypesCasingAndValue($tokens, $sequenceLocation);
    }

    /**
     * @param array<int, Token> $sequence
     */
    private function fixStrictTypesCasingAndValue(Tokens $tokens, array $sequence)
    {
        /** @var int $index */
        /** @var Token $token */
        foreach ($sequence as $index => $token) {
            if ($token->isGivenKind(T_STRING)) {
                $tokens[$index] = new Token([T_STRING, strtolower($token->getContent())]);

                continue;
            }
            if ($token->isGivenKind(T_LNUMBER)) {
                $tokens[$index] = new Token([T_LNUMBER, '1']);

                break;
            }
        }
    }

    private function insertSequence(Tokens $tokens)
    {
        $sequence = [
            new Token([T_DECLARE, 'declare']),
            new Token('('),
            new Token([T_STRING, 'strict_types']),
            new Token('='),
            new Token([T_LNUMBER, '1']),
            new Token(')'),
            new Token(';'),
        ];
        $endIndex = \count($sequence);

        $tokens->insertAt(1, $sequence);

        // start index of the sequence is always 1 here, 0 is always open tag
        // transform "<?php\n" to "<?php " if needed
        if (false !== strpos($tokens[0]->getContent(), "\n")) {
            $tokens[0] = new Token([$tokens[0]->getId(), trim($tokens[0]->getContent()).' ']);
        }

        if ($endIndex === \count($tokens) - 1) {
            return; // no more tokens afters sequence, single_blank_line_at_eof might add a line
        }

        $lineEnding = $this->whitespacesConfig->getLineEnding();
        if (!$tokens[1 + $endIndex]->isWhitespace()) {
            $tokens->insertAt(1 + $endIndex, new Token([T_WHITESPACE, $lineEnding]));

            return;
        }

        $content = $tokens[1 + $endIndex]->getContent();
        $tokens[1 + $endIndex] = new Token([T_WHITESPACE, $lineEnding.ltrim($content, " \t")]);
    }
}
