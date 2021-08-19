<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\TokenBinding;

use Assert\Assertion;
use Psr\Http\Message\ServerRequestInterface;

final class SecTokenBindingHandler implements TokenBindingHandler
{
    public function check(TokenBinding $tokenBinding, ServerRequestInterface $request): void
    {
        if (TokenBinding::TOKEN_BINDING_STATUS_PRESENT !== $tokenBinding->getStatus()) {
            return;
        }

        Assertion::true($request->hasHeader('Sec-Token-Binding'), 'The header parameter "Sec-Token-Binding" is missing.');
        $tokenBindingIds = $request->getHeader('Sec-Token-Binding');
        Assertion::count($tokenBindingIds, 1, 'The header parameter "Sec-Token-Binding" is invalid.');
        $tokenBindingId = reset($tokenBindingIds);
        Assertion::eq($tokenBindingId, $tokenBinding->getId(), 'The header parameter "Sec-Token-Binding" is invalid.');
    }
}
