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

namespace PhpCsFixer\Fixer\Naming;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Fred Cox <mcfedr@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NoHomoglyphNamesFixer extends AbstractFixer
{
    /**
     * Used the program https://github.com/mcfedr/homoglyph-download
     * to generate this list from
     * http://homoglyphs.net/?text=abcdefghijklmnopqrstuvwxyz&lang=en&exc7=1&exc8=1&exc13=1&exc14=1.
     *
     * Symbols replaced include
     * - Latin homoglyphs
     * - IPA extensions
     * - Greek and Coptic
     * - Cyrillic
     * - Cyrillic Supplement
     * - Letterlike Symbols
     * - Latin Numbers
     * - Fullwidth Latin
     *
     * This is not the complete list of unicode homographs, but limited
     * to those you are more likely to have typed/copied by accident
     *
     * @var array
     */
    private static $replacements = [
        'O' => '0',
        '０' => '0',
        'I' => '1',
        '１' => '1',
        '２' => '2',
        '３' => '3',
        '４' => '4',
        '５' => '5',
        '６' => '6',
        '７' => '7',
        '８' => '8',
        '９' => '9',
        'Α' => 'A',
        'А' => 'A',
        'Ａ' => 'A',
        'ʙ' => 'B',
        'Β' => 'B',
        'В' => 'B',
        'Ｂ' => 'B',
        'Ϲ' => 'C',
        'С' => 'C',
        'Ⅽ' => 'C',
        'Ｃ' => 'C',
        'Ⅾ' => 'D',
        'Ｄ' => 'D',
        'Ε' => 'E',
        'Е' => 'E',
        'Ｅ' => 'E',
        'Ϝ' => 'F',
        'Ｆ' => 'F',
        'ɢ' => 'G',
        'Ԍ' => 'G',
        'Ｇ' => 'G',
        'ʜ' => 'H',
        'Η' => 'H',
        'Н' => 'H',
        'Ｈ' => 'H',
        'l' => 'I',
        'Ι' => 'I',
        'І' => 'I',
        'Ⅰ' => 'I',
        'Ｉ' => 'I',
        'Ј' => 'J',
        'Ｊ' => 'J',
        'Κ' => 'K',
        'К' => 'K',
        'K' => 'K',
        'Ｋ' => 'K',
        'ʟ' => 'L',
        'Ⅼ' => 'L',
        'Ｌ' => 'L',
        'Μ' => 'M',
        'М' => 'M',
        'Ⅿ' => 'M',
        'Ｍ' => 'M',
        'ɴ' => 'N',
        'Ν' => 'N',
        'Ｎ' => 'N',
        'Ο' => 'O',
        'О' => 'O',
        'Ｏ' => 'O',
        'Ρ' => 'P',
        'Р' => 'P',
        'Ｐ' => 'P',
        'Ｑ' => 'Q',
        'ʀ' => 'R',
        'Ｒ' => 'R',
        'Ѕ' => 'S',
        'Ｓ' => 'S',
        'Τ' => 'T',
        'Т' => 'T',
        'Ｔ' => 'T',
        'Ｕ' => 'U',
        'Ѵ' => 'V',
        'Ⅴ' => 'V',
        'Ｖ' => 'V',
        'Ｗ' => 'W',
        'Χ' => 'X',
        'Х' => 'X',
        'Ⅹ' => 'X',
        'Ｘ' => 'X',
        'ʏ' => 'Y',
        'Υ' => 'Y',
        'Ү' => 'Y',
        'Ｙ' => 'Y',
        'Ζ' => 'Z',
        'Ｚ' => 'Z',
        '＿' => '_',
        'ɑ' => 'a',
        'а' => 'a',
        'ａ' => 'a',
        'Ь' => 'b',
        'ｂ' => 'b',
        'ϲ' => 'c',
        'с' => 'c',
        'ⅽ' => 'c',
        'ｃ' => 'c',
        'ԁ' => 'd',
        'ⅾ' => 'd',
        'ｄ' => 'd',
        'е' => 'e',
        'ｅ' => 'e',
        'ｆ' => 'f',
        'ɡ' => 'g',
        'ｇ' => 'g',
        'һ' => 'h',
        'ｈ' => 'h',
        'ɩ' => 'i',
        'і' => 'i',
        'ⅰ' => 'i',
        'ｉ' => 'i',
        'ј' => 'j',
        'ｊ' => 'j',
        'ｋ' => 'k',
        'ⅼ' => 'l',
        'ｌ' => 'l',
        'ⅿ' => 'm',
        'ｍ' => 'm',
        'ｎ' => 'n',
        'ο' => 'o',
        'о' => 'o',
        'ｏ' => 'o',
        'р' => 'p',
        'ｐ' => 'p',
        'ｑ' => 'q',
        'ｒ' => 'r',
        'ѕ' => 's',
        'ｓ' => 's',
        'ｔ' => 't',
        'ｕ' => 'u',
        'ν' => 'v',
        'ѵ' => 'v',
        'ⅴ' => 'v',
        'ｖ' => 'v',
        'ѡ' => 'w',
        'ｗ' => 'w',
        'х' => 'x',
        'ⅹ' => 'x',
        'ｘ' => 'x',
        'у' => 'y',
        'ｙ' => 'y',
        'ｚ' => 'z',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replace accidental usage of homoglyphs (non ascii characters) in names.',
            [new CodeSample("<?php \$nаmе = 'wrong \"a\" character';\n")],
            null,
            'Renames classes and cannot rename the files. You might have string references to renamed code (`$$name`).'
        );
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
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_VARIABLE, T_STRING]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_VARIABLE, T_STRING])) {
                continue;
            }

            $replaced = Preg::replaceCallback('/[^[:ascii:]]/u', static function ($matches) {
                return isset(self::$replacements[$matches[0]])
                    ? self::$replacements[$matches[0]]
                    : $matches[0]
                ;
            }, $token->getContent(), -1, $count);

            if ($count) {
                $tokens->offsetSet($index, new Token([$token->getId(), $replaced]));
            }
        }
    }
}
