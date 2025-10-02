<?php
namespace Aws\Api;

/**
 * Represents a timestamp shape.
 */
class TimestampShape extends Shape
{
    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'timestamp';
        parent::__construct($definition, $shapeMap);
    }

    /**
     * Formats a timestamp value for a service.
     *
     * @param mixed  $value  Value to format
     * @param string $format Format used to serialize the value
     *
     * @return int|string
     * @throws \UnexpectedValueException if the format is unknown.
     * @throws \InvalidArgumentException if the value is an unsupported type.
     */
    public static function format($value, $format)
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->getTimestamp();
        } elseif (is_string($value)) {
            $value = strtotime($value);
        } elseif (!is_int($value)) {
            throw new \InvalidArgumentException('Unable to handle the provided'
                . ' timestamp type: ' . gettype($value));
        }

        switch ($format) {
            case 'iso8601':
                return gmdate('Y-m-d\TH:i:s\Z', $value);
            case 'rfc822':
                return gmdate('D, d M Y H:i:s \G\M\T', $value);
            case 'unixTimestamp':
                return $value;
            default:
                throw new \UnexpectedValueException('Unknown timestamp format: '
                    . $format);
        }
    }
}
