<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\CssSelector\Parser\Handler;

use Symfony\Component\CssSelector\Parser\Reader;
use Symfony\Component\CssSelector\Parser\Token;
use Symfony\Component\CssSelector\Parser\Tokenizer\TokenizerEscaping;
use Symfony\Component\CssSelector\Parser\Tokenizer\TokenizerPatterns;
use Symfony\Component\CssSelector\Parser\TokenStream;

/**
 * CSS selector comment handler.
 *
 * This component is a port of the Python cssselect library,
 * which is copyright Ian Bicking, @see https://github.com/SimonSapin/cssselect.
 *
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class IdentifierHandler implements HandlerInterface
{
    private TokenizerPatterns $patterns;
    private TokenizerEscaping $escaping;

    public function __construct(TokenizerPatterns $patterns, TokenizerEscaping $escaping)
    {
        $this->patterns = $patterns;
        $this->escaping = $escaping;
    }

    public function handle(Reader $reader, TokenStream $stream): bool
    {
        $match = $reader->findPattern($this->patterns->getIdentifierPattern());

        if (!$match) {
            return false;
        }

        $value = $this->escaping->escapeUnicode($match[0]);
        $stream->push(new Token(Token::TYPE_IDENTIFIER, $value, $reader->getPosition()));
        $reader->moveForward(\strlen($match[0]));

        return true;
    }
}
