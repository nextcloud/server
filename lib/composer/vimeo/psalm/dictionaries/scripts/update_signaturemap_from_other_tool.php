<?php

$long_options = [
    'foreign-map-a:',
    'foreign-map-b:',
    'local-map-a:',
    'local-map-b:',
];

$options = getopt('', $long_options);

$foreign_a = require($options['foreign-map-a']);
$foreign_b = require($options['foreign-map-b']);

$local_a = require($options['local-map-a']);
$local_b = require($options['local-map-b']);

$added_foreign_functions = array_diff_key($foreign_b, $foreign_a);
$removed_foreign_functions = array_diff_key($foreign_a, $foreign_b);

// get all functions changed in the foreign map that haven't been changed
// in the between local maps
$useful_foreign_functions = array_diff_key(
    array_intersect_key(get_changed_functions($foreign_a, $foreign_b), $local_a),
    get_changed_functions($local_a, $local_b)
);

$new_local = array_diff_key(
    array_merge($added_foreign_functions, $local_b, $useful_foreign_functions),
    $removed_foreign_functions
);

uksort($new_local, fn($a, $b) => strtolower($a) <=> strtolower($b));

foreach ($new_local as $name => $data) {
    if (!is_array($data)) {
        throw new \UnexpectedValueException('bad data for ' . $name);
    }
    $return_type = array_shift($data);
    echo '\'' . str_replace("'", "\'", $name) . '\' => [\'' . str_replace("'", "\'", $return_type) . '\'';

    if ($data) {
        $signature = [];

        foreach ($data as $param_name => $type) {
            $signature[] = '\'' . str_replace("'", "\'", $param_name) . '\'=>\'' . str_replace("'", "\'", $type) . '\'';
        }

        echo ', ' . implode(', ', $signature);
    }

    echo '],' . "\n";
}


function get_changed_functions(array $a, array $b) {
    $changed_functions = [];

    foreach (array_intersect_key($a, $b) as $function_name => $a_data) {
        if (json_encode($b[$function_name]) !== json_encode($a_data)) {
            $changed_functions[$function_name] = $b[$function_name];
        }
    }

    return $changed_functions;
}
