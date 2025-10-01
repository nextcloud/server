<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher as NewExpressionRequestMatcher;

trigger_deprecation('symfony/http-foundation', '6.2', 'The "%s" class is deprecated, use "%s" instead.', ExpressionRequestMatcher::class, NewExpressionRequestMatcher::class);

/**
 * ExpressionRequestMatcher uses an expression to match a Request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since Symfony 6.2, use "Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher" instead
 */
class ExpressionRequestMatcher extends RequestMatcher
{
    private ExpressionLanguage $language;
    private Expression|string $expression;

    /**
     * @return void
     */
    public function setExpression(ExpressionLanguage $language, Expression|string $expression)
    {
        $this->language = $language;
        $this->expression = $expression;
    }

    public function matches(Request $request): bool
    {
        if (!isset($this->language)) {
            throw new \LogicException('Unable to match the request as the expression language is not available. Try running "composer require symfony/expression-language".');
        }

        return $this->language->evaluate($this->expression, [
            'request' => $request,
            'method' => $request->getMethod(),
            'path' => rawurldecode($request->getPathInfo()),
            'host' => $request->getHost(),
            'ip' => $request->getClientIp(),
            'attributes' => $request->attributes->all(),
        ]) && parent::matches($request);
    }
}
