<?php
namespace Aws\Arn;

use Aws\Arn\Exception\InvalidArnException;

/**
 * Amazon Resource Names (ARNs) uniquely identify AWS resources. The Arn class
 * parses and stores a generic ARN object representation that can apply to any
 * service resource.
 *
 * @internal
 */
class Arn implements ArnInterface
{
    protected $data;
    protected $string;

    public static function parse($string)
    {
        $data = [
            'arn' => null,
            'partition' => null,
            'service' => null,
            'region' => null,
            'account_id' => null,
            'resource' => null,
        ];

        $length = strlen($string);
        $lastDelim = 0;
        $numComponents = 0;
        for ($i = 0; $i < $length; $i++) {

            if (($numComponents < 5 && $string[$i] === ':')) {
                // Split components between delimiters
                $data[key($data)] = substr($string, $lastDelim, $i - $lastDelim);

                // Do not include delimiter character itself
                $lastDelim = $i + 1;
                next($data);
                $numComponents++;
            }

            if ($i === $length - 1) {
                // Put the remainder in the last component.
                if (in_array($numComponents, [5])) {
                    $data['resource'] = substr($string, $lastDelim);
                } else {
                    // If there are < 5 components, put remainder in current
                    // component.
                    $data[key($data)] = substr($string, $lastDelim);
                }
            }
        }

        return $data;
    }

    public function __construct($data)
    {
        if (is_array($data)) {
            $this->data = $data;
        } elseif (is_string($data)) {
            $this->data = static::parse($data);
        } else {
            throw new InvalidArnException('Constructor accepts a string or an'
                . ' array as an argument.');
        }

        static::validate($this->data);
    }

    public function __toString()
    {
        if (!isset($this->string)) {
            $components = [
                $this->getPrefix(),
                $this->getPartition(),
                $this->getService(),
                $this->getRegion(),
                $this->getAccountId(),
                $this->getResource(),
            ];

            $this->string = implode(':', $components);
        }
        return $this->string;
    }

    public function getPrefix()
    {
        return $this->data['arn'];
    }

    public function getPartition()
    {
        return $this->data['partition'];
    }

    public function getService()
    {
        return $this->data['service'];
    }

    public function getRegion()
    {
        return $this->data['region'];
    }

    public function getAccountId()
    {
        return $this->data['account_id'];
    }

    public function getResource()
    {
        return $this->data['resource'];
    }

    public function toArray()
    {
        return $this->data;
    }

    /**
     * Minimally restrictive generic ARN validation
     *
     * @param array $data
     */
    protected static function validate(array $data)
    {
        if ($data['arn'] !== 'arn') {
            throw new InvalidArnException("The 1st component of an ARN must be"
                . " 'arn'.");
        }

        if (empty($data['partition'])) {
            throw new InvalidArnException("The 2nd component of an ARN"
                . " represents the partition and must not be empty.");
        }

        if (empty($data['service'])) {
            throw new InvalidArnException("The 3rd component of an ARN"
                . " represents the service and must not be empty.");
        }

        if (empty($data['resource'])) {
            throw new InvalidArnException("The 6th component of an ARN"
                . " represents the resource information and must not be empty."
                . " Individual service ARNs may include additional delimiters"
                . " to further qualify resources.");
        }
    }

    protected static function validateAccountId($data, $arnName)
    {
        if (!self::isValidHostLabel($data['account_id'])) {
            throw new InvalidArnException("The 5th component of a {$arnName}"
                . " is required, represents the account ID, and"
                . " must be a valid host label.");
        }
    }

    protected static function validateRegion($data, $arnName)
    {
        if (empty($data['region'])) {
            throw new InvalidArnException("The 4th component of a {$arnName}"
                . " represents the region and must not be empty.");
        }
    }

    /**
     * Validates whether a string component is a valid host label
     *
     * @param $string
     * @return bool
     */
    protected static function isValidHostLabel($string)
    {
        if (empty($string) || strlen($string) > 63) {
            return false;
        }
        if ($value = preg_match("/^[a-zA-Z0-9-]+$/", $string)) {
            return true;
        }
        return false;
    }
}