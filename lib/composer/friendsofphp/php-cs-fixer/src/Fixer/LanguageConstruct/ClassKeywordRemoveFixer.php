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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class ClassKeywordRemoveFixer extends AbstractFixer
{
    /**
     * @var string[]
     */
    private $imports = [];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Converts `::class` keywords to FQCN strings.',
            [
                new CodeSample(
                    '<?php

use Foo\Bar\Baz;

$className = Baz::class;
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoUnusedImportsFixer.
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
        return $tokens->isTokenKindFound(CT::T_CLASS_CONSTANT);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $namespacesAnalyzer = new NamespacesAnalyzer();

        $previousNamespaceScopeEndIndex = 0;
        foreach ($namespacesAnalyzer->getDeclarations($tokens) as $declaration) {
            $this->replaceClassKeywordsSection($tokens, '', $previousNamespaceScopeEndIndex, $declaration->getStartIndex());
            $this->replaceClassKeywordsSection($tokens, $declaration->getFullName(), $declaration->getStartIndex(), $declaration->getScopeEndIndex());
            $previousNamespaceScopeEndIndex = $declaration->getScopeEndIndex();
        }

        $this->replaceClassKeywordsSection($tokens, '', $previousNamespaceScopeEndIndex, $tokens->count() - 1);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function storeImports(Tokens $tokens, $startIndex, $endIndex)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $this->imports = [];

        /** @var int $index */
        foreach ($tokensAnalyzer->getImportUseIndexes() as $index) {
            if ($index < $startIndex || $index > $endIndex) {
                continue;
            }

            $import = '';
            while ($index = $tokens->getNextMeaningfulToken($index)) {
                if ($tokens[$index]->equalsAny([';', [CT::T_GROUP_IMPORT_BRACE_OPEN]]) || $tokens[$index]->isGivenKind(T_AS)) {
                    break;
                }

                $import .= $tokens[$index]->getContent();
            }

            // Imports group (PHP 7 spec)
            if ($tokens[$index]->isGivenKind(CT::T_GROUP_IMPORT_BRACE_OPEN)) {
                $groupEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_GROUP_IMPORT_BRACE, $index);
                $groupImports = array_map(
                    static function ($import) {
                        return trim($import);
                    },
                    explode(',', $tokens->generatePartialCode($index + 1, $groupEndIndex - 1))
                );
                foreach ($groupImports as $groupImport) {
                    $groupImportParts = array_map(static function ($import) {
                        return trim($import);
                    }, explode(' as ', $groupImport));
                    if (2 === \count($groupImportParts)) {
                        $this->imports[$groupImportParts[1]] = $import.$groupImportParts[0];
                    } else {
                        $this->imports[] = $import.$groupImport;
                    }
                }
            } elseif ($tokens[$index]->isGivenKind(T_AS)) {
                $aliasIndex = $tokens->getNextMeaningfulToken($index);
                $alias = $tokens[$aliasIndex]->getContent();
                $this->imports[$alias] = $import;
            } else {
                $this->imports[] = $import;
            }
        }
    }

    /**
     * @param string $namespace
     * @param int    $startIndex
     * @param int    $endIndex
     */
    private function replaceClassKeywordsSection(Tokens $tokens, $namespace, $startIndex, $endIndex)
    {
        if ($endIndex - $startIndex < 3) {
            return;
        }

        $this->storeImports($tokens, $startIndex, $endIndex);

        $ctClassTokens = $tokens->findGivenKind(CT::T_CLASS_CONSTANT, $startIndex, $endIndex);
        foreach (array_reverse(array_keys($ctClassTokens)) as $classIndex) {
            $this->replaceClassKeyword($tokens, $namespace, $classIndex);
        }
    }

    /**
     * @param string $namespace
     * @param int    $classIndex
     */
    private function replaceClassKeyword(Tokens $tokens, $namespace, $classIndex)
    {
        $classEndIndex = $tokens->getPrevMeaningfulToken($classIndex);
        $classEndIndex = $tokens->getPrevMeaningfulToken($classEndIndex);

        if ($tokens[$classEndIndex]->equalsAny([[T_STRING, 'self'], [T_STATIC, 'static'], [T_STRING, 'parent']], false)) {
            return;
        }

        $classBeginIndex = $classEndIndex;
        while (true) {
            $prev = $tokens->getPrevMeaningfulToken($classBeginIndex);
            if (!$tokens[$prev]->isGivenKind([T_NS_SEPARATOR, T_STRING])) {
                break;
            }

            $classBeginIndex = $prev;
        }

        $classString = $tokens->generatePartialCode(
            $tokens[$classBeginIndex]->isGivenKind(T_NS_SEPARATOR)
                ? $tokens->getNextMeaningfulToken($classBeginIndex)
                : $classBeginIndex,
            $classEndIndex
        );

        $classImport = false;
        foreach ($this->imports as $alias => $import) {
            if ($classString === $alias) {
                $classImport = $import;

                break;
            }

            $classStringArray = explode('\\', $classString);
            $namespaceToTest = $classStringArray[0];

            if (0 === strcmp($namespaceToTest, substr($import, -\strlen($namespaceToTest)))) {
                $classImport = $import;

                break;
            }
        }

        for ($i = $classBeginIndex; $i <= $classIndex; ++$i) {
            if (!$tokens[$i]->isComment() && !($tokens[$i]->isWhitespace() && false !== strpos($tokens[$i]->getContent(), "\n"))) {
                $tokens->clearAt($i);
            }
        }

        $tokens->insertAt($classBeginIndex, new Token([
            T_CONSTANT_ENCAPSED_STRING,
            "'".$this->makeClassFQN($namespace, $classImport, $classString)."'",
        ]));
    }

    /**
     * @param string       $namespace
     * @param false|string $classImport
     * @param string       $classString
     *
     * @return string
     */
    private function makeClassFQN($namespace, $classImport, $classString)
    {
        if (false === $classImport) {
            return ('' !== $namespace ? ($namespace.'\\') : '').$classString;
        }

        $classStringArray = explode('\\', $classString);
        $classStringLength = \count($classStringArray);
        $classImportArray = explode('\\', $classImport);
        $classImportLength = \count($classImportArray);

        if (1 === $classStringLength) {
            return $classImport;
        }

        return implode('\\', array_merge(
            \array_slice($classImportArray, 0, $classImportLength - $classStringLength + 1),
            $classStringArray
        ));
    }
}
