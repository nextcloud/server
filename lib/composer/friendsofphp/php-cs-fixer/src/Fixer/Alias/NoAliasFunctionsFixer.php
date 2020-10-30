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
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NoAliasFunctionsFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private $aliases = [];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $internalSet = [
        'chop' => 'rtrim',
        'close' => 'closedir',
        'doubleval' => 'floatval',
        'fputs' => 'fwrite',
        'get_required_files' => 'get_included_files',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'join' => 'implode',
        'key_exists' => 'array_key_exists',
        'magic_quotes_runtime' => 'set_magic_quotes_runtime',
        'pos' => 'current',
        'show_source' => 'highlight_file',
        'sizeof' => 'count',
        'strchr' => 'strstr',
        'user_error' => 'trigger_error',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $imapSet = [
        'imap_create' => 'imap_createmailbox',
        'imap_fetchtext' => 'imap_body',
        'imap_header' => 'imap_headerinfo',
        'imap_listmailbox' => 'imap_list',
        'imap_listsubscribed' => 'imap_lsub',
        'imap_rename' => 'imap_renamemailbox',
        'imap_scan' => 'imap_listscan',
        'imap_scanmailbox' => 'imap_listscan',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $mbregSet = [
        'mbereg' => 'mb_ereg',
        'mbereg_match' => 'mb_ereg_match',
        'mbereg_replace' => 'mb_ereg_replace',
        'mbereg_search' => 'mb_ereg_search',
        'mbereg_search_getpos' => 'mb_ereg_search_getpos',
        'mbereg_search_getregs' => 'mb_ereg_search_getregs',
        'mbereg_search_init' => 'mb_ereg_search_init',
        'mbereg_search_pos' => 'mb_ereg_search_pos',
        'mbereg_search_regs' => 'mb_ereg_search_regs',
        'mbereg_search_setpos' => 'mb_ereg_search_setpos',
        'mberegi' => 'mb_eregi',
        'mberegi_replace' => 'mb_eregi_replace',
        'mbregex_encoding' => 'mb_regex_encoding',
        'mbsplit' => 'mb_split',
    ];

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->aliases = [];
        foreach ($this->configuration['sets'] as $set) {
            if ('@all' === $set) {
                $this->aliases = self::$internalSet;
                $this->aliases = array_merge($this->aliases, self::$imapSet);
                $this->aliases = array_merge($this->aliases, self::$mbregSet);

                break;
            }
            if ('@internal' === $set) {
                $this->aliases = array_merge($this->aliases, self::$internalSet);
            } elseif ('@IMAP' === $set) {
                $this->aliases = array_merge($this->aliases, self::$imapSet);
            } elseif ('@mbreg' === $set) {
                $this->aliases = array_merge($this->aliases, self::$mbregSet);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Master functions shall be used instead of aliases.',
            [
                new CodeSample(
                    '<?php
$a = chop($b);
close($b);
$a = doubleval($b);
$a = fputs($b, $c);
$a = get_required_files();
ini_alter($b, $c);
$a = is_double($b);
$a = is_integer($b);
$a = is_long($b);
$a = is_real($b);
$a = is_writeable($b);
$a = join($glue, $pieces);
$a = key_exists($key, $array);
magic_quotes_runtime($new_setting);
$a = pos($array);
$a = show_source($filename, true);
$a = sizeof($b);
$a = strchr($haystack, $needle);
$a = imap_header($imap_stream, 1);
user_error($message);
mbereg_search_getregs();
'
                ),
                new CodeSample(
                    '<?php
$a = is_double($b);
mbereg_search_getregs();
',
                    ['sets' => ['@mbreg']]
                ),
            ],
            null,
            'Risky when any of the alias functions are overridden.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before ImplodeCallFixer, PhpUnitDedicateAssertFixer.
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
        $functionsAnalyzer = new FunctionsAnalyzer();

        /** @var Token $token */
        foreach ($tokens->findGivenKind(T_STRING) as $index => $token) {
            // check mapping hit
            $tokenContent = strtolower($token->getContent());
            if (!isset($this->aliases[$tokenContent])) {
                continue;
            }

            // skip expressions without parameters list
            $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];
            if (!$nextToken->equals('(')) {
                continue;
            }

            if (!$functionsAnalyzer->isGlobalFunctionCall($tokens, $index)) {
                continue;
            }

            $tokens[$index] = new Token([T_STRING, $this->aliases[$tokenContent]]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $sets = ['@internal', '@IMAP', '@mbreg', '@all'];

        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('sets', 'List of sets to fix. Defined sets are `@internal` (native functions), `@IMAP` (IMAP functions), `@mbreg` (from `ext-mbstring`) `@all` (all listed sets).'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([new AllowedValueSubset($sets)])
                ->setDefault(['@internal', '@IMAP'])
                ->getOption(),
        ]);
    }
}
