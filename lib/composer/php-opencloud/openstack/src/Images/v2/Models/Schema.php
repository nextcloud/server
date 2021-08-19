<?php

declare(strict_types=1);

namespace OpenStack\Images\v2\Models;

use JsonSchema\Validator;

class Schema extends \OpenStack\Common\JsonSchema\Schema
{
    public function __construct($data, Validator $validator = null)
    {
        if (!isset($data->type)) {
            $data->type = 'object';
        }

        foreach ($data->properties as $propertyName => &$property) {
            if (false !== strpos($property->description, 'READ-ONLY')) {
                $property->readOnly = true;
            }
        }

        parent::__construct($data, $validator);
    }
}
