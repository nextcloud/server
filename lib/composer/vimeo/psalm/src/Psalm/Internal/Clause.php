<?php
namespace Psalm\Internal;

use function array_diff;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function json_encode;
use function ksort;
use function md5;
use function sort;
use function array_unique;
use function strpos;

/**
 * @internal
 *
 * @psalm-immutable
 */
class Clause
{
    /** @var int */
    public $creating_conditional_id;

    /** @var int */
    public $creating_object_id;

    /**
     * An array of strings of the form
     * [
     *     '$a' => ['falsy'],
     *     '$b' => ['!falsy'],
     *     '$c' => ['!null'],
     *     '$d' => ['string', 'int']
     * ]
     *
     * representing the formula
     *
     * !$a || $b || $c !== null || is_string($d) || is_int($d)
     *
     * @var array<string, non-empty-list<string>>
     */
    public $possibilities;

    /**
     * An array of things that are not true
     * [
     *     '$a' => ['!falsy'],
     *     '$b' => ['falsy'],
     *     '$c' => ['null'],
     *     '$d' => ['!string', '!int']
     * ]
     * represents the formula
     *
     * $a && !$b && $c === null && !is_string($d) && !is_int($d)
     *
     * @var array<string, non-empty-list<string>>|null
     */
    public $impossibilities;

    /** @var bool */
    public $wedge;

    /** @var bool */
    public $reconcilable;

    /** @var bool */
    public $generated = false;

    /** @var array<string, bool> */
    public $redefined_vars = [];

    /** @var string|int */
    public $hash;

    /**
     * @param array<string, non-empty-list<string>>  $possibilities
     * @param array<string, bool> $redefined_vars
     */
    public function __construct(
        array $possibilities,
        int $creating_conditional_id,
        int $creating_object_id,
        bool $wedge = false,
        bool $reconcilable = true,
        bool $generated = false,
        array $redefined_vars = []
    ) {
        $this->possibilities = $possibilities;
        $this->wedge = $wedge;
        $this->reconcilable = $reconcilable;
        $this->generated = $generated;
        $this->redefined_vars = $redefined_vars;
        $this->creating_conditional_id = $creating_conditional_id;
        $this->creating_object_id = $creating_object_id;

        if ($wedge || !$reconcilable) {
            $this->hash = ($wedge ? 'w' : '') . $creating_object_id;
        } else {
            ksort($possibilities);

            foreach ($possibilities as $i => $_) {
                sort($possibilities[$i]);
            }

            $this->hash = md5((string) json_encode($possibilities));
        }
    }

    public function contains(Clause $other_clause): bool
    {
        if (count($other_clause->possibilities) > count($this->possibilities)) {
            return false;
        }

        foreach ($other_clause->possibilities as $var => $possible_types) {
            if (!isset($this->possibilities[$var]) || count(array_diff($possible_types, $this->possibilities[$var]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        $clause_strings = array_map(
            /**
             * @param string $var_id
             * @param non-empty-list<string> $values
             *
             * @return string
             */
            function ($var_id, $values): string {
                $var_id_clauses = array_map(
                    /**
                     * @param string $value
                     *
                     * @return string
                     */
                    function ($value) use ($var_id): string {
                        if ($value === 'falsy') {
                            return '!' . $var_id;
                        }

                        if ($value === '!falsy') {
                            return $var_id;
                        }

                        $negate = false;

                        if ($value[0] === '!') {
                            $negate = true;
                            $value = \substr($value, 1);
                        }

                        if ($value[0] === '=') {
                            $value = \substr($value, 1);
                        }

                        if ($negate) {
                            return $var_id . ' is not ' . $value;
                        }

                        return $var_id . ' is ' . $value;
                    },
                    $values
                );

                if (count($var_id_clauses) > 1) {
                    return '(' . implode(') || (', $var_id_clauses) . ')';
                }

                return $var_id_clauses[0];
            },
            array_keys($this->possibilities),
            array_values($this->possibilities)
        );

        if (count($clause_strings) > 1) {
            return '(' . implode(') || (', $clause_strings) . ')';
        }

        return \reset($clause_strings);
    }

    public function makeUnique() : self
    {
        $possibilities = $this->possibilities;

        foreach ($possibilities as $var_id => $var_possibilities) {
            $possibilities[$var_id] = array_values(array_unique($var_possibilities));
        }

        return new self(
            $possibilities,
            $this->creating_conditional_id,
            $this->creating_object_id,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars
        );
    }

    public function removePossibilities(string $var_id) : ?self
    {
        $possibilities = $this->possibilities;
        unset($possibilities[$var_id]);

        if (!$possibilities) {
            return null;
        }

        return new self(
            $possibilities,
            $this->creating_conditional_id,
            $this->creating_object_id,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars
        );
    }

    /**
     * @param non-empty-list<string> $clause_var_possibilities
     */
    public function addPossibilities(string $var_id, array $clause_var_possibilities) : self
    {
        $possibilities = $this->possibilities;
        $possibilities[$var_id] = $clause_var_possibilities;

        return new self(
            $possibilities,
            $this->creating_conditional_id,
            $this->creating_object_id,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars
        );
    }

    public function calculateNegation() : self
    {
        if ($this->impossibilities !== null) {
            return $this;
        }

        $impossibilities = [];

        foreach ($this->possibilities as $var_id => $possibility) {
            $impossibility = [];

            foreach ($possibility as $type) {
                if (($type[0] !== '=' && $type[0] !== '~'
                        && (!isset($type[1]) || ($type[1] !== '=' && $type[1] !== '~')))
                    || strpos($type, '(')
                    || strpos($type, 'getclass-')
                ) {
                    $impossibility[] = \Psalm\Type\Algebra::negateType($type);
                }
            }

            if ($impossibility) {
                $impossibilities[$var_id] = $impossibility;
            }
        }

        $clause = clone $this;

        $clause->impossibilities = $impossibilities;

        return $clause;
    }
}
