# JSON Schema for PHP

[![Build Status](https://travis-ci.org/justinrainbow/json-schema.svg?branch=master)](https://travis-ci.org/justinrainbow/json-schema)
[![Latest Stable Version](https://poser.pugx.org/justinrainbow/json-schema/v/stable.png)](https://packagist.org/packages/justinrainbow/json-schema)
[![Total Downloads](https://poser.pugx.org/justinrainbow/json-schema/downloads.png)](https://packagist.org/packages/justinrainbow/json-schema)

A PHP Implementation for validating `JSON` Structures against a given `Schema`.

See [json-schema](http://json-schema.org/) for more details.

## Installation

### Library

```bash
git clone https://github.com/justinrainbow/json-schema.git
```

### Composer

[Install PHP Composer](https://getcomposer.org/doc/00-intro.md)

```bash
composer require justinrainbow/json-schema
```

## Usage

```php
<?php

$data = json_decode(file_get_contents('data.json'));

// Validate
$validator = new JsonSchema\Validator;
$validator->validate($data, (object)['$ref' => 'file://' . realpath('schema.json')]);

if ($validator->isValid()) {
    echo "The supplied JSON validates against the schema.\n";
} else {
    echo "JSON does not validate. Violations:\n";
    foreach ($validator->getErrors() as $error) {
        echo sprintf("[%s] %s\n", $error['property'], $error['message']);
    }
}
```

### Type coercion

If you're validating data passed to your application via HTTP, you can cast strings and booleans to
the expected types defined by your schema:

```php
<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;

$request = (object)[
    'processRefund'=>"true",
    'refundAmount'=>"17"
];

$validator->validate(
    $request, (object) [
    "type"=>"object",
        "properties"=>(object)[
            "processRefund"=>(object)[
                "type"=>"boolean"
            ],
            "refundAmount"=>(object)[
                "type"=>"number"
            ]
        ]
    ],
    Constraint::CHECK_MODE_COERCE_TYPES
); // validates!

is_bool($request->processRefund); // true
is_int($request->refundAmount); // true
```

A shorthand method is also available:
```PHP
$validator->coerce($request, $schema);
// equivalent to $validator->validate($data, $schema, Constraint::CHECK_MODE_COERCE_TYPES);
```

### Default values

If your schema contains default values, you can have these automatically applied during validation:

```php
<?php

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

$request = (object)[
    'refundAmount'=>17
];

$validator = new Validator();

$validator->validate(
    $request,
    (object)[
        "type"=>"object",
        "properties"=>(object)[
            "processRefund"=>(object)[
                "type"=>"boolean",
                "default"=>true
            ]
        ]
    ],
    Constraint::CHECK_MODE_APPLY_DEFAULTS
); //validates, and sets defaults for missing properties

is_bool($request->processRefund); // true
$request->processRefund; // true
```

### With inline references

```php
<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;

$jsonSchema = <<<'JSON'
{
    "type": "object",
    "properties": {
        "data": {
            "oneOf": [
                { "$ref": "#/definitions/integerData" },
                { "$ref": "#/definitions/stringData" }
            ]
        }
    },
    "required": ["data"],
    "definitions": {
        "integerData" : {
            "type": "integer",
            "minimum" : 0
        },
        "stringData" : {
            "type": "string"
        }
    }
}
JSON;

// Schema must be decoded before it can be used for validation
$jsonSchemaObject = json_decode($jsonSchema);

// The SchemaStorage can resolve references, loading additional schemas from file as needed, etc.
$schemaStorage = new SchemaStorage();

// This does two things:
// 1) Mutates $jsonSchemaObject to normalize the references (to file://mySchema#/definitions/integerData, etc)
// 2) Tells $schemaStorage that references to file://mySchema... should be resolved by looking in $jsonSchemaObject
$schemaStorage->addSchema('file://mySchema', $jsonSchemaObject);

// Provide $schemaStorage to the Validator so that references can be resolved during validation
$jsonValidator = new Validator( new Factory($schemaStorage));

// JSON must be decoded before it can be validated
$jsonToValidateObject = json_decode('{"data":123}');

// Do validation (use isValid() and getErrors() to check the result)
$jsonValidator->validate($jsonToValidateObject, $jsonSchemaObject);
```

### Configuration Options
A number of flags are available to alter the behavior of the validator. These can be passed as the
third argument to `Validator::validate()`, or can be provided as the third argument to
`Factory::__construct()` if you wish to persist them across multiple `validate()` calls.

| Flag | Description |
|------|-------------|
| `Constraint::CHECK_MODE_NORMAL` | Validate in 'normal' mode - this is the default |
| `Constraint::CHECK_MODE_TYPE_CAST` | Enable fuzzy type checking for associative arrays and objects |
| `Constraint::CHECK_MODE_COERCE_TYPES` | Convert data types to match the schema where possible |
| `Constraint::CHECK_MODE_APPLY_DEFAULTS` | Apply default values from the schema if not set |
| `Constraint::CHECK_MODE_ONLY_REQUIRED_DEFAULTS` | When applying defaults, only set values that are required |
| `Constraint::CHECK_MODE_EXCEPTIONS` | Throw an exception immediately if validation fails |
| `Constraint::CHECK_MODE_DISABLE_FORMAT` | Do not validate "format" constraints |
| `Constraint::CHECK_MODE_VALIDATE_SCHEMA` | Validate the schema as well as the provided document |

Please note that using `Constraint::CHECK_MODE_COERCE_TYPES` or `Constraint::CHECK_MODE_APPLY_DEFAULTS`
will modify your original data.

## Running the tests

```bash
composer test                            # run all unit tests
composer testOnly TestClass              # run specific unit test class
composer testOnly TestClass::testMethod  # run specific unit test method
composer style-check                     # check code style for errors
composer style-fix                       # automatically fix code style errors
```
