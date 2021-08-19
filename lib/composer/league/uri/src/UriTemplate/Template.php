<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\UriTemplate;

use League\Uri\Exceptions\SyntaxError;
use League\Uri\Exceptions\TemplateCanNotBeExpanded;
use function array_merge;
use function array_unique;
use function gettype;
use function is_object;
use function is_string;
use function method_exists;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function strpos;
use const PREG_SET_ORDER;

final class Template
{
    /**
     * Expression regular expression pattern.
     */
    private const REGEXP_EXPRESSION_DETECTOR = '/\{[^\}]*\}/x';

    /**
     * @var string
     */
    private $template;

    /**
     * @var array<string, Expression>
     */
    private $expressions = [];

    /**
     * @var array<string>
     */
    private $variableNames;

    private function __construct(string $template, Expression ...$expressions)
    {
        $this->template = $template;
        $variableNames = [];
        foreach ($expressions as $expression) {
            $this->expressions[$expression->toString()] = $expression;
            $variableNames[] = $expression->variableNames();
        }
        $this->variableNames = array_unique(array_merge([], ...$variableNames));
    }

    /**
     * {@inheritDoc}
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['template'], ...array_values($properties['expressions']));
    }

    /**
     * @param object|string $template a string or an object with the __toString method
     *
     * @throws \TypeError  if the template is not a string or an object with the __toString method
     * @throws SyntaxError if the template contains invalid expressions
     * @throws SyntaxError if the template contains invalid variable specification
     */
    public static function createFromString($template): self
    {
        if (is_object($template) && method_exists($template, '__toString')) {
            $template = (string) $template;
        }

        if (!is_string($template)) {
            throw new \TypeError(sprintf('The template must be a string or a stringable object %s given.', gettype($template)));
        }

        /** @var string $remainder */
        $remainder = preg_replace(self::REGEXP_EXPRESSION_DETECTOR, '', $template);
        if (false !== strpos($remainder, '{') || false !== strpos($remainder, '}')) {
            throw new SyntaxError('The template "'.$template.'" contains invalid expressions.');
        }

        $names = [];
        preg_match_all(self::REGEXP_EXPRESSION_DETECTOR, $template, $findings, PREG_SET_ORDER);
        $arguments = [];
        foreach ($findings as $finding) {
            if (!isset($names[$finding[0]])) {
                $arguments[] = Expression::createFromString($finding[0]);
                $names[$finding[0]] = 1;
            }
        }

        return new self($template, ...$arguments);
    }

    public function toString(): string
    {
        return $this->template;
    }

    /**
     * @return array<string>
     */
    public function variableNames(): array
    {
        return $this->variableNames;
    }

    /**
     * @throws TemplateCanNotBeExpanded if the variables is an array and a ":" modifier needs to be applied
     * @throws TemplateCanNotBeExpanded if the variables contains nested array values
     */
    public function expand(VariableBag $variables): string
    {
        $uriString = $this->template;
        /** @var Expression $expression */
        foreach ($this->expressions as $pattern => $expression) {
            $uriString = str_replace($pattern, $expression->expand($variables), $uriString);
        }

        return $uriString;
    }
}
