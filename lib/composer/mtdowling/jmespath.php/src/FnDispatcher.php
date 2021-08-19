<?php
namespace JmesPath;

/**
 * Dispatches to named JMESPath functions using a single function that has the
 * following signature:
 *
 *     mixed $result = fn(string $function_name, array $args)
 */
class FnDispatcher
{
    /**
     * Gets a cached instance of the default function implementations.
     *
     * @return FnDispatcher
     */
    public static function getInstance()
    {
        static $instance = null;
        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * @param string $fn   Function name.
     * @param array  $args Function arguments.
     *
     * @return mixed
     */
    public function __invoke($fn, array $args)
    {
        return $this->{'fn_' . $fn}($args);
    }

    private function fn_abs(array $args)
    {
        $this->validate('abs', $args, [['number']]);
        return abs($args[0]);
    }

    private function fn_avg(array $args)
    {
        $this->validate('avg', $args, [['array']]);
        $sum = $this->reduce('avg:0', $args[0], ['number'], function ($a, $b) {
            return Utils::add($a, $b);
        });
        return $args[0] ? ($sum / count($args[0])) : null;
    }

    private function fn_ceil(array $args)
    {
        $this->validate('ceil', $args, [['number']]);
        return ceil($args[0]);
    }

    private function fn_contains(array $args)
    {
        $this->validate('contains', $args, [['string', 'array'], ['any']]);
        if (is_array($args[0])) {
            return in_array($args[1], $args[0]);
        } elseif (is_string($args[1])) {
            return mb_strpos($args[0], $args[1], 0, 'UTF-8') !== false;
        } else {
            return null;
        }
    }

    private function fn_ends_with(array $args)
    {
        $this->validate('ends_with', $args, [['string'], ['string']]);
        list($search, $suffix) = $args;
        return $suffix === '' || mb_substr($search, -mb_strlen($suffix, 'UTF-8'), null, 'UTF-8') === $suffix;
    }

    private function fn_floor(array $args)
    {
        $this->validate('floor', $args, [['number']]);
        return floor($args[0]);
    }

    private function fn_not_null(array $args)
    {
        if (!$args) {
            throw new \RuntimeException(
                "not_null() expects 1 or more arguments, 0 were provided"
            );
        }

        return array_reduce($args, function ($carry, $item) {
            return $carry !== null ? $carry : $item;
        });
    }

    private function fn_join(array $args)
    {
        $this->validate('join', $args, [['string'], ['array']]);
        $fn = function ($a, $b, $i) use ($args) {
            return $i ? ($a . $args[0] . $b) : $b;
        };
        return $this->reduce('join:0', $args[1], ['string'], $fn);
    }

    private function fn_keys(array $args)
    {
        $this->validate('keys', $args, [['object']]);
        return array_keys((array) $args[0]);
    }

    private function fn_length(array $args)
    {
        $this->validate('length', $args, [['string', 'array', 'object']]);
        return is_string($args[0]) ? mb_strlen($args[0], 'UTF-8') : count((array) $args[0]);
    }

    private function fn_max(array $args)
    {
        $this->validate('max', $args, [['array']]);
        $fn = function ($a, $b) {
            return $a >= $b ? $a : $b;
        };
        return $this->reduce('max:0', $args[0], ['number', 'string'], $fn);
    }

    private function fn_max_by(array $args)
    {
        $this->validate('max_by', $args, [['array'], ['expression']]);
        $expr = $this->wrapExpression('max_by:1', $args[1], ['number', 'string']);
        $fn = function ($carry, $item, $index) use ($expr) {
            return $index
                ? ($expr($carry) >= $expr($item) ? $carry : $item)
                : $item;
        };
        return $this->reduce('max_by:1', $args[0], ['any'], $fn);
    }

    private function fn_min(array $args)
    {
        $this->validate('min', $args, [['array']]);
        $fn = function ($a, $b, $i) {
            return $i && $a <= $b ? $a : $b;
        };
        return $this->reduce('min:0', $args[0], ['number', 'string'], $fn);
    }

    private function fn_min_by(array $args)
    {
        $this->validate('min_by', $args, [['array'], ['expression']]);
        $expr = $this->wrapExpression('min_by:1', $args[1], ['number', 'string']);
        $i = -1;
        $fn = function ($a, $b) use ($expr, &$i) {
            return ++$i ? ($expr($a) <= $expr($b) ? $a : $b) : $b;
        };
        return $this->reduce('min_by:1', $args[0], ['any'], $fn);
    }

    private function fn_reverse(array $args)
    {
        $this->validate('reverse', $args, [['array', 'string']]);
        if (is_array($args[0])) {
            return array_reverse($args[0]);
        } elseif (is_string($args[0])) {
            return strrev($args[0]);
        } else {
            throw new \RuntimeException('Cannot reverse provided argument');
        }
    }

    private function fn_sum(array $args)
    {
        $this->validate('sum', $args, [['array']]);
        $fn = function ($a, $b) {
            return Utils::add($a, $b);
        };
        return $this->reduce('sum:0', $args[0], ['number'], $fn);
    }

    private function fn_sort(array $args)
    {
        $this->validate('sort', $args, [['array']]);
        $valid = ['string', 'number'];
        return Utils::stableSort($args[0], function ($a, $b) use ($valid) {
            $this->validateSeq('sort:0', $valid, $a, $b);
            return strnatcmp($a, $b);
        });
    }

    private function fn_sort_by(array $args)
    {
        $this->validate('sort_by', $args, [['array'], ['expression']]);
        $expr = $args[1];
        $valid = ['string', 'number'];
        return Utils::stableSort(
            $args[0],
            function ($a, $b) use ($expr, $valid) {
                $va = $expr($a);
                $vb = $expr($b);
                $this->validateSeq('sort_by:0', $valid, $va, $vb);
                return strnatcmp($va, $vb);
            }
        );
    }

    private function fn_starts_with(array $args)
    {
        $this->validate('starts_with', $args, [['string'], ['string']]);
        list($search, $prefix) = $args;
        return $prefix === '' || mb_strpos($search, $prefix, 0, 'UTF-8') === 0;
    }

    private function fn_type(array $args)
    {
        $this->validateArity('type', count($args), 1);
        return Utils::type($args[0]);
    }

    private function fn_to_string(array $args)
    {
        $this->validateArity('to_string', count($args), 1);
        $v = $args[0];
        if (is_string($v)) {
            return $v;
        } elseif (is_object($v)
            && !($v instanceof \JsonSerializable)
            && method_exists($v, '__toString')
        ) {
            return (string) $v;
        }

        return json_encode($v);
    }

    private function fn_to_number(array $args)
    {
        $this->validateArity('to_number', count($args), 1);
        $value = $args[0];
        $type = Utils::type($value);
        if ($type == 'number') {
            return $value;
        } elseif ($type == 'string' && is_numeric($value)) {
            return mb_strpos($value, '.', 0, 'UTF-8') ? (float) $value : (int) $value;
        } else {
            return null;
        }
    }

    private function fn_values(array $args)
    {
        $this->validate('values', $args, [['array', 'object']]);
        return array_values((array) $args[0]);
    }

    private function fn_merge(array $args)
    {
        if (!$args) {
            throw new \RuntimeException(
                "merge() expects 1 or more arguments, 0 were provided"
            );
        }

        return call_user_func_array('array_replace', $args);
    }

    private function fn_to_array(array $args)
    {
        $this->validate('to_array', $args, [['any']]);

        return Utils::isArray($args[0]) ? $args[0] : [$args[0]];
    }

    private function fn_map(array $args)
    {
        $this->validate('map', $args, [['expression'], ['any']]);
        $result = [];
        foreach ($args[1] as $a) {
            $result[] = $args[0]($a);
        }
        return $result;
    }

    private function typeError($from, $msg)
    {
        if (mb_strpos($from, ':', 0, 'UTF-8')) {
            list($fn, $pos) = explode(':', $from);
            throw new \RuntimeException(
                sprintf('Argument %d of %s %s', $pos, $fn, $msg)
            );
        } else {
            throw new \RuntimeException(
                sprintf('Type error: %s %s', $from, $msg)
            );
        }
    }

    private function validateArity($from, $given, $expected)
    {
        if ($given != $expected) {
            $err = "%s() expects {$expected} arguments, {$given} were provided";
            throw new \RuntimeException(sprintf($err, $from));
        }
    }

    private function validate($from, $args, $types = [])
    {
        $this->validateArity($from, count($args), count($types));
        foreach ($args as $index => $value) {
            if (!isset($types[$index]) || !$types[$index]) {
                continue;
            }
            $this->validateType("{$from}:{$index}", $value, $types[$index]);
        }
    }

    private function validateType($from, $value, array $types)
    {
        if ($types[0] == 'any'
            || in_array(Utils::type($value), $types)
            || ($value === [] && in_array('object', $types))
        ) {
            return;
        }
        $msg = 'must be one of the following types: ' . implode(', ', $types)
            . '. ' . Utils::type($value) . ' found';
        $this->typeError($from, $msg);
    }

    /**
     * Validates value A and B, ensures they both are correctly typed, and of
     * the same type.
     *
     * @param string   $from   String of function:argument_position
     * @param array    $types  Array of valid value types.
     * @param mixed    $a      Value A
     * @param mixed    $b      Value B
     */
    private function validateSeq($from, array $types, $a, $b)
    {
        $ta = Utils::type($a);
        $tb = Utils::type($b);

        if ($ta !== $tb) {
            $msg = "encountered a type mismatch in sequence: {$ta}, {$tb}";
            $this->typeError($from, $msg);
        }

        $typeMatch = ($types && $types[0] == 'any') || in_array($ta, $types);
        if (!$typeMatch) {
            $msg = 'encountered a type error in sequence. The argument must be '
                . 'an array of ' . implode('|', $types) . ' types. '
                . "Found {$ta}, {$tb}.";
            $this->typeError($from, $msg);
        }
    }

    /**
     * Reduces and validates an array of values to a single value using a fn.
     *
     * @param string   $from   String of function:argument_position
     * @param array    $values Values to reduce.
     * @param array    $types  Array of valid value types.
     * @param callable $reduce Reduce function that accepts ($carry, $item).
     *
     * @return mixed
     */
    private function reduce($from, array $values, array $types, callable $reduce)
    {
        $i = -1;
        return array_reduce(
            $values,
            function ($carry, $item) use ($from, $types, $reduce, &$i) {
                if (++$i > 0) {
                    $this->validateSeq($from, $types, $carry, $item);
                }
                return $reduce($carry, $item, $i);
            }
        );
    }

    /**
     * Validates the return values of expressions as they are applied.
     *
     * @param string   $from  Function name : position
     * @param callable $expr  Expression function to validate.
     * @param array    $types Array of acceptable return type values.
     *
     * @return callable Returns a wrapped function
     */
    private function wrapExpression($from, callable $expr, array $types)
    {
        list($fn, $pos) = explode(':', $from);
        $from = "The expression return value of argument {$pos} of {$fn}";
        return function ($value) use ($from, $expr, $types) {
            $value = $expr($value);
            $this->validateType($from, $value, $types);
            return $value;
        };
    }

    /** @internal Pass function name validation off to runtime */
    public function __call($name, $args)
    {
        $name = str_replace('fn_', '', $name);
        throw new \RuntimeException("Call to undefined function {$name}");
    }
}
