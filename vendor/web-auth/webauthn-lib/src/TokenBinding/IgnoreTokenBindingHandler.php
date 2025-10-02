<?php

declare(strict_types=1);

namespace Webauthn\TokenBinding;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @deprecated Since 4.3.0 and will be removed in 5.0.0
 * @infection-ignore-all
 */
final class IgnoreTokenBindingHandler implements TokenBindingHandler
{
    public static function create(): self
    {
        return new self();
    }

    public function check(TokenBinding $tokenBinding, ServerRequestInterface $request): void
    {
        //Does nothing
    }
}
