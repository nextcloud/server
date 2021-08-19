<?php

require __DIR__ . '/../vendor/autoload.php';

$data = json_decode(file_get_contents('data.json'));

// Validate
$validator = new JsonSchema\Validator();
$validator->check($data, (object) array('$ref' => 'file://' . realpath('schema.json')));

if ($validator->isValid()) {
    echo "The supplied JSON validates against the schema.\n";
} else {
    echo "JSON does not validate. Violations:\n";
    foreach ($validator->getErrors() as $error) {
        echo sprintf("[%s] %s\n", $error['property'], $error['message']);
    }
}
