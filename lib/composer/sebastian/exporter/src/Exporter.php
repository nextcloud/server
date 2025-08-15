<?php declare(strict_types=1);
/*
 * This file is part of sebastian/exporter.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Exporter;

use function bin2hex;
use function count;
use function get_resource_type;
use function gettype;
use function implode;
use function ini_get;
use function ini_set;
use function is_array;
use function is_float;
use function is_object;
use function is_resource;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function spl_object_id;
use function sprintf;
use function str_repeat;
use function str_replace;
use function var_export;
use BackedEnum;
use SebastianBergmann\RecursionContext\Context;
use SplObjectStorage;
use UnitEnum;

final class Exporter
{
    /**
     * Exports a value as a string.
     *
     * The output of this method is similar to the output of print_r(), but
     * improved in various aspects:
     *
     *  - NULL is rendered as "null" (instead of "")
     *  - TRUE is rendered as "true" (instead of "1")
     *  - FALSE is rendered as "false" (instead of "")
     *  - Strings are always quoted with single quotes
     *  - Carriage returns and newlines are normalized to \n
     *  - Recursion and repeated rendering is treated properly
     */
    public function export(mixed $value, int $indentation = 0): string
    {
        return $this->recursiveExport($value, $indentation);
    }

    public function shortenedRecursiveExport(array &$data, ?Context $context = null): string
    {
        $result   = [];
        $exporter = new self;

        if (!$context) {
            $context = new Context;
        }

        $array = $data;

        /* @noinspection UnusedFunctionResultInspection */
        $context->add($data);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($context->contains($data[$key]) !== false) {
                    $result[] = '*RECURSION*';
                } else {
                    $result[] = sprintf('[%s]', $this->shortenedRecursiveExport($data[$key], $context));
                }
            } else {
                $result[] = $exporter->shortenedExport($value);
            }
        }

        return implode(', ', $result);
    }

    /**
     * Exports a value into a single-line string.
     *
     * The output of this method is similar to the output of
     * SebastianBergmann\Exporter\Exporter::export().
     *
     * Newlines are replaced by the visible string '\n'.
     * Contents of arrays and objects (if any) are replaced by '...'.
     */
    public function shortenedExport(mixed $value): string
    {
        if (is_string($value)) {
            $string = str_replace("\n", '', $this->export($value));

            if (mb_strlen($string) > 40) {
                return mb_substr($string, 0, 30) . '...' . mb_substr($string, -7);
            }

            return $string;
        }

        if ($value instanceof BackedEnum) {
            return sprintf(
                '%s Enum (%s, %s)',
                $value::class,
                $value->name,
                $this->export($value->value),
            );
        }

        if ($value instanceof UnitEnum) {
            return sprintf(
                '%s Enum (%s)',
                $value::class,
                $value->name,
            );
        }

        if (is_object($value)) {
            return sprintf(
                '%s Object (%s)',
                $value::class,
                count($this->toArray($value)) > 0 ? '...' : '',
            );
        }

        if (is_array($value)) {
            return sprintf(
                '[%s]',
                count($value) > 0 ? '...' : '',
            );
        }

        return $this->export($value);
    }

    /**
     * Converts an object to an array containing all of its private, protected
     * and public properties.
     */
    public function toArray(mixed $value): array
    {
        if (!is_object($value)) {
            return (array) $value;
        }

        $array = [];

        foreach ((array) $value as $key => $val) {
            // Exception traces commonly reference hundreds to thousands of
            // objects currently loaded in memory. Including them in the result
            // has a severe negative performance impact.
            if ("\0Error\0trace" === $key || "\0Exception\0trace" === $key) {
                continue;
            }

            // properties are transformed to keys in the following way:
            // private   $propertyName => "\0ClassName\0propertyName"
            // protected $propertyName => "\0*\0propertyName"
            // public    $propertyName => "propertyName"
            if (preg_match('/^\0.+\0(.+)$/', (string) $key, $matches)) {
                $key = $matches[1];
            }

            // See https://github.com/php/php-src/commit/5721132
            if ($key === "\0gcdata") {
                continue;
            }

            $array[$key] = $val;
        }

        // Some internal classes like SplObjectStorage do not work with the
        // above (fast) mechanism nor with reflection in Zend.
        // Format the output similarly to print_r() in this case
        if ($value instanceof SplObjectStorage) {
            foreach ($value as $_value) {
                $array['Object #' . spl_object_id($_value)] = [
                    'obj' => $_value,
                    'inf' => $value->getInfo(),
                ];
            }

            $value->rewind();
        }

        return $array;
    }

    private function recursiveExport(mixed &$value, int $indentation, ?Context $processed = null): string
    {
        if ($value === null) {
            return 'null';
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if (is_float($value)) {
            $precisionBackup = ini_get('precision');

            ini_set('precision', '-1');

            try {
                $valueStr = (string) $value;

                if ((string) (int) $value === $valueStr) {
                    return $valueStr . '.0';
                }

                return $valueStr;
            } finally {
                ini_set('precision', $precisionBackup);
            }
        }

        if (gettype($value) === 'resource (closed)') {
            return 'resource (closed)';
        }

        if (is_resource($value)) {
            return sprintf(
                'resource(%d) of type (%s)',
                $value,
                get_resource_type($value),
            );
        }

        if ($value instanceof BackedEnum) {
            return sprintf(
                '%s Enum #%d (%s, %s)',
                $value::class,
                spl_object_id($value),
                $value->name,
                $this->export($value->value, $indentation),
            );
        }

        if ($value instanceof UnitEnum) {
            return sprintf(
                '%s Enum #%d (%s)',
                $value::class,
                spl_object_id($value),
                $value->name,
            );
        }

        if (is_string($value)) {
            // Match for most non-printable chars somewhat taking multibyte chars into account
            if (preg_match('/[^\x09-\x0d\x1b\x20-\xff]/', $value)) {
                return 'Binary String: 0x' . bin2hex($value);
            }

            return "'" .
            str_replace(
                '<lf>',
                "\n",
                str_replace(
                    ["\r\n", "\n\r", "\r", "\n"],
                    ['\r\n<lf>', '\n\r<lf>', '\r<lf>', '\n<lf>'],
                    $value,
                ),
            ) .
            "'";
        }

        $whitespace = str_repeat(' ', 4 * $indentation);

        if (!$processed) {
            $processed = new Context;
        }

        if (is_array($value)) {
            if (($key = $processed->contains($value)) !== false) {
                return 'Array &' . $key;
            }

            $array  = $value;
            $key    = $processed->add($value);
            $values = '';

            if (count($array) > 0) {
                foreach ($array as $k => $v) {
                    $values .=
                        $whitespace
                        . '    ' .
                        $this->recursiveExport($k, $indentation)
                        . ' => ' .
                        $this->recursiveExport($value[$k], $indentation + 1, $processed)
                        . ",\n";
                }

                $values = "\n" . $values . $whitespace;
            }

            return 'Array &' . (string) $key . ' [' . $values . ']';
        }

        if (is_object($value)) {
            $class = $value::class;

            if ($processed->contains($value)) {
                return $class . ' Object #' . spl_object_id($value);
            }

            $processed->add($value);
            $values = '';
            $array  = $this->toArray($value);

            if (count($array) > 0) {
                foreach ($array as $k => $v) {
                    $values .=
                        $whitespace
                        . '    ' .
                        $this->recursiveExport($k, $indentation)
                        . ' => ' .
                        $this->recursiveExport($v, $indentation + 1, $processed)
                        . ",\n";
                }

                $values = "\n" . $values . $whitespace;
            }

            return $class . ' Object #' . spl_object_id($value) . ' (' . $values . ')';
        }

        return var_export($value, true);
    }
}
