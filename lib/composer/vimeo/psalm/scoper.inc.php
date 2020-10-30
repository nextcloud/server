<?php

return [
    'patchers' => [
        function ($filePath, $prefix, $contents) {
            //
            // PHP-Parser patch
            //
            if ($filePath === 'vendor/nikic/php-parser/lib/PhpParser/NodeAbstract.php') {
                $length = 15 + strlen($prefix) + 1;

                return preg_replace(
                    '%strpos\((.+?)\) \+ 15%',
                    sprintf('strpos($1) + %d', $length),
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            return str_replace(
                '\\'.$prefix.'\Composer\Autoload\ClassLoader',
                '\Composer\Autoload\ClassLoader',
                $contents
            );
        },
        function ($filePath, $prefix, $contents) {
            if (strpos($filePath, 'src/Psalm') === 0) {
                return str_replace(
                    [' \\PhpParser\\'],
                    [' \\' . $prefix . '\\PhpParser\\'],
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if (strpos($filePath, 'vendor/phpmyadmin/sql-parser/src/Context.php') === 0) {
                return str_replace(
                    '\'' . $prefix,
                    '\'\\\\' . $prefix,
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if (strpos($filePath, 'vendor/openlss') === 0) {
                return str_replace(
                    $prefix . '\\DomDocument',
                    'DomDocument',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if ($filePath === 'src/psalm.php') {
                return str_replace(
                    '\\' . $prefix . '\\PSALM_VERSION',
                    'PSALM_VERSION',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            $ret = str_replace(
                $prefix . '\\Psalm\\',
                'Psalm\\',
                $contents
            );
            return $ret;
        },
    ],
    'whitelist' => [
        \Composer\Autoload\ClassLoader::class,
        'Psalm\*',
    ],
    'files-whitelist' => [
        'src/spl_object_id.php',
    ],
];
