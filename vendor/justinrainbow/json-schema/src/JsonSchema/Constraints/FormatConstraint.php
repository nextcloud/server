<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\ConstraintError;
use JsonSchema\Entity\JsonPointer;
use JsonSchema\Rfc3339;
use JsonSchema\Tool\Validator\RelativeReferenceValidator;
use JsonSchema\Tool\Validator\UriValidator;

/**
 * Validates against the "format" property
 *
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 *
 * @see   http://tools.ietf.org/html/draft-zyp-json-schema-03#section-5.23
 */
class FormatConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (!isset($schema->format) || $this->factory->getConfig(self::CHECK_MODE_DISABLE_FORMAT)) {
            return;
        }

        switch ($schema->format) {
            case 'date':
                if (is_string($element) && !$date = $this->validateDateTime($element, 'Y-m-d')) {
                    $this->addError(ConstraintError::FORMAT_DATE(), $path, [
                            'date' => $element,
                            'format' => $schema->format
                        ]
                    );
                }
                break;

            case 'time':
                if (is_string($element) && !$this->validateDateTime($element, 'H:i:s')) {
                    $this->addError(ConstraintError::FORMAT_TIME(), $path, [
                            'time' => json_encode($element),
                            'format' => $schema->format,
                        ]
                    );
                }
                break;

            case 'date-time':
                if (is_string($element) && null === Rfc3339::createFromString($element)) {
                    $this->addError(ConstraintError::FORMAT_DATE_TIME(), $path, [
                            'dateTime' => json_encode($element),
                            'format' => $schema->format
                        ]
                    );
                }
                break;

            case 'utc-millisec':
                if (!$this->validateDateTime($element, 'U')) {
                    $this->addError(ConstraintError::FORMAT_DATE_UTC(), $path, [
                        'value' => $element,
                        'format' => $schema->format]);
                }
                break;

            case 'regex':
                if (!$this->validateRegex($element)) {
                    $this->addError(ConstraintError::FORMAT_REGEX(), $path, [
                            'value' => $element,
                            'format' => $schema->format
                        ]
                    );
                }
                break;

            case 'color':
                if (!$this->validateColor($element)) {
                    $this->addError(ConstraintError::FORMAT_COLOR(), $path, ['format' => $schema->format]);
                }
                break;

            case 'style':
                if (!$this->validateStyle($element)) {
                    $this->addError(ConstraintError::FORMAT_STYLE(), $path, ['format' => $schema->format]);
                }
                break;

            case 'phone':
                if (!$this->validatePhone($element)) {
                    $this->addError(ConstraintError::FORMAT_PHONE(), $path, ['format' => $schema->format]);
                }
                break;

            case 'uri':
                if (is_string($element) && !UriValidator::isValid($element)) {
                    $this->addError(ConstraintError::FORMAT_URL(), $path, ['format' => $schema->format]);
                }
                break;

            case 'uriref':
            case 'uri-reference':
                if (is_string($element) && !(UriValidator::isValid($element) || RelativeReferenceValidator::isValid($element))) {
                    $this->addError(ConstraintError::FORMAT_URL(), $path, ['format' => $schema->format]);
                }
                break;

            case 'email':
                if (is_string($element) && null === filter_var($element, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE | FILTER_FLAG_EMAIL_UNICODE)) {
                    $this->addError(ConstraintError::FORMAT_EMAIL(), $path, ['format' => $schema->format]);
                }
                break;

            case 'ip-address':
            case 'ipv4':
                if (is_string($element) && null === filter_var($element, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV4)) {
                    $this->addError(ConstraintError::FORMAT_IP(), $path, ['format' => $schema->format]);
                }
                break;

            case 'ipv6':
                if (is_string($element) && null === filter_var($element, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV6)) {
                    $this->addError(ConstraintError::FORMAT_IP(), $path, ['format' => $schema->format]);
                }
                break;

            case 'host-name':
            case 'hostname':
                if (!$this->validateHostname($element)) {
                    $this->addError(ConstraintError::FORMAT_HOSTNAME(), $path, ['format' => $schema->format]);
                }
                break;

            default:
                // Empty as it should be:
                // The value of this keyword is called a format attribute. It MUST be a string.
                // A format attribute can generally only validate a given set of instance types.
                // If the type of the instance to validate is not in this set, validation for
                // this format attribute and instance SHOULD succeed.
                // http://json-schema.org/latest/json-schema-validation.html#anchor105
                break;
        }
    }

    protected function validateDateTime($datetime, $format)
    {
        $dt = \DateTime::createFromFormat($format, (string) $datetime);

        if (!$dt) {
            return false;
        }

        if ($datetime === $dt->format($format)) {
            return true;
        }

        return false;
    }

    protected function validateRegex($regex)
    {
        if (!is_string($regex)) {
            return true;
        }

        return false !== @preg_match(self::jsonPatternToPhpRegex($regex), '');
    }

    protected function validateColor($color)
    {
        if (!is_string($color)) {
            return true;
        }

        if (in_array(strtolower($color), ['aqua', 'black', 'blue', 'fuchsia',
            'gray', 'green', 'lime', 'maroon', 'navy', 'olive', 'orange', 'purple',
            'red', 'silver', 'teal', 'white', 'yellow'])) {
            return true;
        }

        return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color);
    }

    protected function validateStyle($style)
    {
        $properties     = explode(';', rtrim($style, ';'));
        $invalidEntries = preg_grep('/^\s*[-a-z]+\s*:\s*.+$/i', $properties, PREG_GREP_INVERT);

        return empty($invalidEntries);
    }

    protected function validatePhone($phone)
    {
        return preg_match('/^\+?(\(\d{3}\)|\d{3}) \d{3} \d{4}$/', $phone);
    }

    protected function validateHostname($host)
    {
        if (!is_string($host)) {
            return true;
        }

        $hostnameRegex = '/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/i';

        return preg_match($hostnameRegex, $host);
    }
}
