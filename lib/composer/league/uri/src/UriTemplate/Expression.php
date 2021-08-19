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
use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function explode;
use function implode;
use function preg_match;
use function rawurlencode;
use function str_replace;
use function strpos;
use function substr;

final class Expression
{
    /**
     * Expression regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc6570#section-2.2
     */
    private const REGEXP_EXPRESSION = '/^\{
        (?:
            (?<operator>[\.\/;\?&\=,\!@\|\+#])?
            (?<variables>[^\}]*)
        )
    \}$/x';

    /**
     * Reserved Operator characters.
     *
     * @link https://tools.ietf.org/html/rfc6570#section-2.2
     */
    private const RESERVED_OPERATOR = '=,!@|';

    /**
     * Processing behavior according to the expression type operator.
     *
     * @link https://tools.ietf.org/html/rfc6570#appendix-A
     */
    private const OPERATOR_HASH_LOOKUP = [
        ''  => ['prefix' => '',  'joiner' => ',', 'query' => false],
        '+' => ['prefix' => '',  'joiner' => ',', 'query' => false],
        '#' => ['prefix' => '#', 'joiner' => ',', 'query' => false],
        '.' => ['prefix' => '.', 'joiner' => '.', 'query' => false],
        '/' => ['prefix' => '/', 'joiner' => '/', 'query' => false],
        ';' => ['prefix' => ';', 'joiner' => ';', 'query' => true],
        '?' => ['prefix' => '?', 'joiner' => '&', 'query' => true],
        '&' => ['prefix' => '&', 'joiner' => '&', 'query' => true],
    ];

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string
     */
    private $joiner;

    /**
     * @var array<VarSpecifier>
     */
    private $varSpecifiers;

    /**
     * @var array<string>
     */
    private $variableNames;

    /**
     * @var string
     */
    private $expressionString;

    private function __construct(string $operator, VarSpecifier ...$varSpecifiers)
    {
        $this->operator = $operator;
        $this->varSpecifiers = $varSpecifiers;
        $this->joiner = self::OPERATOR_HASH_LOOKUP[$operator]['joiner'];
        $this->variableNames = $this->setVariableNames();
        $this->expressionString = $this->setExpressionString();
    }

    /**
     * @return array<string>
     */
    private function setVariableNames(): array
    {
        $mapper = static function (VarSpecifier $varSpecifier): string {
            return $varSpecifier->name();
        };

        return array_unique(array_map($mapper, $this->varSpecifiers));
    }

    private function setExpressionString(): string
    {
        $mapper = static function (VarSpecifier $variable): string {
            return $variable->toString();
        };

        $varSpecifierString = implode(',', array_map($mapper, $this->varSpecifiers));

        return '{'.$this->operator.$varSpecifierString.'}';
    }

    /**
     * {@inheritDoc}
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['operator'], ...$properties['varSpecifiers']);
    }

    /**
     * @throws SyntaxError if the expression is invalid
     * @throws SyntaxError if the operator used in the expression is invalid
     * @throws SyntaxError if the variable specifiers is invalid
     */
    public static function createFromString(string $expression): self
    {
        if (1 !== preg_match(self::REGEXP_EXPRESSION, $expression, $parts)) {
            throw new SyntaxError('The expression "'.$expression.'" is invalid.');
        }

        /** @var array{operator:string, variables:string} $parts */
        $parts = $parts + ['operator' => ''];
        if ('' !== $parts['operator'] && false !== strpos(self::RESERVED_OPERATOR, $parts['operator'])) {
            throw new SyntaxError('The operator used in the expression "'.$expression.'" is reserved.');
        }

        $mapper = static function (string $varSpec): VarSpecifier {
            return VarSpecifier::createFromString($varSpec);
        };

        return new Expression($parts['operator'], ...array_map($mapper, explode(',', $parts['variables'])));
    }

    /**
     * Returns the expression string representation.
     *
     */
    public function toString(): string
    {
        return $this->expressionString;
    }

    /**
     * @return array<string>
     */
    public function variableNames(): array
    {
        return $this->variableNames;
    }

    public function expand(VariableBag $variables): string
    {
        $parts = [];
        foreach ($this->varSpecifiers as $varSpecifier) {
            $parts[] = $this->replace($varSpecifier, $variables);
        }

        $nullFilter = static function ($value): bool {
            return '' !== $value;
        };

        $expanded = implode($this->joiner, array_filter($parts, $nullFilter));
        if ('' === $expanded) {
            return $expanded;
        }

        $prefix = self::OPERATOR_HASH_LOOKUP[$this->operator]['prefix'];
        if ('' === $prefix) {
            return $expanded;
        }

        return $prefix.$expanded;
    }

    /**
     * Replaces an expression with the given variables.
     *
     * @throws TemplateCanNotBeExpanded if the variables is an array and a ":" modifier needs to be applied
     * @throws TemplateCanNotBeExpanded if the variables contains nested array values
     */
    private function replace(VarSpecifier $varSpec, VariableBag $variables): string
    {
        $value = $variables->fetch($varSpec->name());
        if (null === $value) {
            return '';
        }

        $useQuery = self::OPERATOR_HASH_LOOKUP[$this->operator]['query'];
        [$expanded, $actualQuery] = $this->inject($value, $varSpec, $useQuery);
        if (!$actualQuery) {
            return $expanded;
        }

        if ('&' !== $this->joiner && '' === $expanded) {
            return $varSpec->name();
        }

        return $varSpec->name().'='.$expanded;
    }

    /**
     * @param string|array<string> $value
     *
     * @return array{0:string, 1:bool}
     */
    private function inject($value, VarSpecifier $varSpec, bool $useQuery): array
    {
        if (is_string($value)) {
            return $this->replaceString($value, $varSpec, $useQuery);
        }

        return $this->replaceList($value, $varSpec, $useQuery);
    }

    /**
     * Expands an expression using a string value.
     *
     * @return array{0:string, 1:bool}
     */
    private function replaceString(string $value, VarSpecifier $varSpec, bool $useQuery): array
    {
        if (':' === $varSpec->modifier()) {
            $value = substr($value, 0, $varSpec->position());
        }

        $expanded = rawurlencode($value);
        if ('+' === $this->operator || '#' === $this->operator) {
            return [$this->decodeReserved($expanded), $useQuery];
        }

        return [$expanded, $useQuery];
    }

    /**
     * Expands an expression using a list of values.
     *
     * @param array<string> $value
     *
     * @throws TemplateCanNotBeExpanded if the variables is an array and a ":" modifier needs to be applied
     *
     * @return array{0:string, 1:bool}
     */
    private function replaceList(array $value, VarSpecifier $varSpec, bool $useQuery): array
    {
        if ([] === $value) {
            return ['', false];
        }

        if (':' === $varSpec->modifier()) {
            throw TemplateCanNotBeExpanded::dueToUnableToProcessValueListWithPrefix($varSpec->name());
        }

        $pairs = [];
        $isAssoc = $this->isAssoc($value);
        foreach ($value as $key => $var) {
            if ($isAssoc) {
                $key = rawurlencode((string) $key);
            }

            $var = rawurlencode($var);
            if ('+' === $this->operator || '#' === $this->operator) {
                $var = $this->decodeReserved($var);
            }

            if ('*' === $varSpec->modifier()) {
                if ($isAssoc) {
                    $var = $key.'='.$var;
                } elseif ($key > 0 && $useQuery) {
                    $var = $varSpec->name().'='.$var;
                }
            }

            $pairs[$key] = $var;
        }

        if ('*' === $varSpec->modifier()) {
            if ($isAssoc) {
                // Don't prepend the value name when using the explode
                // modifier with an associative array.
                $useQuery = false;
            }

            return [implode($this->joiner, $pairs), $useQuery];
        }

        if ($isAssoc) {
            // When an associative array is encountered and the
            // explode modifier is not set, then the result must be
            // a comma separated list of keys followed by their
            // respective values.
            foreach ($pairs as $offset => &$data) {
                $data = $offset.','.$data;
            }

            unset($data);
        }

        return [implode(',', $pairs), $useQuery];
    }

    /**
     * Determines if an array is associative.
     *
     * This makes the assumption that input arrays are sequences or hashes.
     * This assumption is a trade-off for accuracy in favor of speed, but it
     * should work in almost every case where input is supplied for a URI
     * template.
     */
    private function isAssoc(array $array): bool
    {
        return [] !== $array && 0 !== array_keys($array)[0];
    }

    /**
     * Removes percent encoding on reserved characters (used with + and # modifiers).
     */
    private function decodeReserved(string $str): string
    {
        static $delimiters = [
            ':', '/', '?', '#', '[', ']', '@', '!', '$',
            '&', '\'', '(', ')', '*', '+', ',', ';', '=',
        ];

        static $delimitersEncoded = [
            '%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40', '%21', '%24',
            '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D',
        ];

        return str_replace($delimitersEncoded, $delimiters, $str);
    }
}
