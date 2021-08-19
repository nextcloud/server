<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp;

/**
 * Block/node types
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class Type
{
    const T_ASSIGN = 'assign';
    const T_AT_ROOT = 'at-root';
    const T_BLOCK = 'block';
    /** @deprecated */
    const T_BREAK = 'break';
    const T_CHARSET = 'charset';
    const T_COLOR = 'color';
    const T_COMMENT = 'comment';
    /** @deprecated */
    const T_CONTINUE = 'continue';
    /** @deprecated */
    const T_CONTROL = 'control';
    const T_CUSTOM_PROPERTY = 'custom';
    const T_DEBUG = 'debug';
    const T_DIRECTIVE = 'directive';
    const T_EACH = 'each';
    const T_ELSE = 'else';
    const T_ELSEIF = 'elseif';
    const T_ERROR = 'error';
    const T_EXPRESSION = 'exp';
    const T_EXTEND = 'extend';
    const T_FOR = 'for';
    const T_FUNCTION = 'function';
    const T_FUNCTION_REFERENCE = 'function-reference';
    const T_FUNCTION_CALL = 'fncall';
    const T_HSL = 'hsl';
    const T_HWB = 'hwb';
    const T_IF = 'if';
    const T_IMPORT = 'import';
    const T_INCLUDE = 'include';
    const T_INTERPOLATE = 'interpolate';
    const T_INTERPOLATED = 'interpolated';
    const T_KEYWORD = 'keyword';
    const T_LIST = 'list';
    const T_MAP = 'map';
    const T_MEDIA = 'media';
    const T_MEDIA_EXPRESSION = 'mediaExp';
    const T_MEDIA_TYPE = 'mediaType';
    const T_MEDIA_VALUE = 'mediaValue';
    const T_MIXIN = 'mixin';
    const T_MIXIN_CONTENT = 'mixin_content';
    const T_NESTED_PROPERTY = 'nestedprop';
    const T_NOT = 'not';
    const T_NULL = 'null';
    const T_NUMBER = 'number';
    const T_RETURN = 'return';
    const T_ROOT = 'root';
    const T_SCSSPHP_IMPORT_ONCE = 'scssphp-import-once';
    const T_SELF = 'self';
    const T_STRING = 'string';
    const T_UNARY = 'unary';
    const T_VARIABLE = 'var';
    const T_WARN = 'warn';
    const T_WHILE = 'while';
}
