<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tokenizer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class CT
{
    const T_ARRAY_INDEX_CURLY_BRACE_CLOSE = 10001;
    const T_ARRAY_INDEX_CURLY_BRACE_OPEN = 10002;
    const T_ARRAY_SQUARE_BRACE_CLOSE = 10003;
    const T_ARRAY_SQUARE_BRACE_OPEN = 10004;
    const T_ARRAY_TYPEHINT = 10005;
    const T_BRACE_CLASS_INSTANTIATION_CLOSE = 10006;
    const T_BRACE_CLASS_INSTANTIATION_OPEN = 10007;
    const T_CLASS_CONSTANT = 10008;
    const T_CONST_IMPORT = 10009;
    const T_CURLY_CLOSE = 10010;
    const T_DESTRUCTURING_SQUARE_BRACE_CLOSE = 10011;
    const T_DESTRUCTURING_SQUARE_BRACE_OPEN = 10012;
    const T_DOLLAR_CLOSE_CURLY_BRACES = 10013;
    const T_DYNAMIC_PROP_BRACE_CLOSE = 10014;
    const T_DYNAMIC_PROP_BRACE_OPEN = 10015;
    const T_DYNAMIC_VAR_BRACE_CLOSE = 10016;
    const T_DYNAMIC_VAR_BRACE_OPEN = 10017;
    const T_FUNCTION_IMPORT = 10018;
    const T_GROUP_IMPORT_BRACE_CLOSE = 10019;
    const T_GROUP_IMPORT_BRACE_OPEN = 10020;
    const T_NAMESPACE_OPERATOR = 10021;
    const T_NULLABLE_TYPE = 10022;
    const T_RETURN_REF = 10023;
    const T_TYPE_ALTERNATION = 10024;
    const T_TYPE_COLON = 10025;
    const T_USE_LAMBDA = 10026;
    const T_USE_TRAIT = 10027;

    private function __construct()
    {
    }

    /**
     * Get name for custom token.
     *
     * @param int $value custom token value
     *
     * @return string
     */
    public static function getName($value)
    {
        if (!self::has($value)) {
            throw new \InvalidArgumentException(sprintf('No custom token was found for "%s".', $value));
        }

        $tokens = self::getMapById();

        return 'CT::'.$tokens[$value];
    }

    /**
     * Check if given custom token exists.
     *
     * @param int $value custom token value
     *
     * @return bool
     */
    public static function has($value)
    {
        $tokens = self::getMapById();

        return isset($tokens[$value]);
    }

    private static function getMapById()
    {
        static $constants;

        if (null === $constants) {
            $reflection = new \ReflectionClass(__CLASS__);
            $constants = array_flip($reflection->getConstants());
        }

        return $constants;
    }
}
