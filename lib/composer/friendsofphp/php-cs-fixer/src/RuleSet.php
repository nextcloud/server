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

namespace PhpCsFixer;

use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion;

/**
 * Set of rules to be used by fixer.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 *
 * @internal
 */
final class RuleSet implements RuleSetInterface
{
    private $setDefinitions = [
        '@PSR1' => [
            'encoding' => true,
            'full_opening_tag' => true,
        ],
        '@PSR2' => [
            '@PSR1' => true,
            'blank_line_after_namespace' => true,
            'braces' => true,
            'class_definition' => true,
            'constant_case' => true,
            'elseif' => true,
            'function_declaration' => true,
            'indentation_type' => true,
            'line_ending' => true,
            'lowercase_keywords' => true,
            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
            'no_break_comment' => true,
            'no_closing_tag' => true,
            'no_spaces_after_function_name' => true,
            'no_spaces_inside_parenthesis' => true,
            'no_trailing_whitespace' => true,
            'no_trailing_whitespace_in_comment' => true,
            'single_blank_line_at_eof' => true,
            'single_class_element_per_statement' => ['elements' => ['property']],
            'single_import_per_statement' => true,
            'single_line_after_imports' => true,
            'switch_case_semicolon_to_colon' => true,
            'switch_case_space' => true,
            'visibility_required' => true,
        ],
        '@Symfony' => [
            '@PSR2' => true,
            'array_syntax' => ['syntax' => 'short'],
            'binary_operator_spaces' => true,
            'blank_line_after_opening_tag' => true,
            'blank_line_before_statement' => [
                'statements' => ['return'],
            ],
            'braces' => [
                'allow_single_line_closure' => true,
            ],
            'cast_spaces' => true,
            'class_attributes_separation' => ['elements' => ['method']],
            'class_definition' => ['single_line' => true],
            'concat_space' => true,
            'declare_equal_normalize' => true,
            'function_typehint_space' => true,
            'include' => true,
            'increment_style' => true,
            'lowercase_cast' => true,
            'lowercase_static_reference' => true,
            'magic_constant_casing' => true,
            'magic_method_casing' => true,
            'method_argument_space' => true,
            'native_function_casing' => true,
            'native_function_type_declaration_casing' => true,
            'new_with_braces' => true,
            'no_blank_lines_after_class_opening' => true,
            'no_blank_lines_after_phpdoc' => true,
            'no_empty_comment' => true,
            'no_empty_phpdoc' => true,
            'no_empty_statement' => true,
            'no_extra_blank_lines' => ['tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ]],
            'no_leading_import_slash' => true,
            'no_leading_namespace_whitespace' => true,
            'no_mixed_echo_print' => true,
            'no_multiline_whitespace_around_double_arrow' => true,
            'no_short_bool_cast' => true,
            'no_singleline_whitespace_before_semicolons' => true,
            'no_spaces_around_offset' => true,
            'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'allow_unused_params' => true],
            'no_trailing_comma_in_list_call' => true,
            'no_trailing_comma_in_singleline_array' => true,
            'no_unneeded_control_parentheses' => true,
            'no_unneeded_curly_braces' => ['namespaces' => true],
            'no_unused_imports' => true,
            'no_whitespace_before_comma_in_array' => true,
            'no_whitespace_in_blank_line' => true,
            'normalize_index_brace' => true,
            'object_operator_without_whitespace' => true,
            'ordered_imports' => true,
            'php_unit_fqcn_annotation' => true,
            'phpdoc_align' => [
                // @TODO: on 3.0 switch whole rule to `=> true`, currently we use custom config that will be default on 3.0
                'tags' => [
                    'method',
                    'param',
                    'property',
                    'return',
                    'throws',
                    'type',
                    'var',
                ],
            ],
            'phpdoc_annotation_without_dot' => true,
            'phpdoc_indent' => true,
            'phpdoc_inline_tag' => true,
            'phpdoc_no_access' => true,
            'phpdoc_no_alias_tag' => true,
            'phpdoc_no_package' => true,
            'phpdoc_no_useless_inheritdoc' => true,
            'phpdoc_return_self_reference' => true,
            'phpdoc_scalar' => true,
            'phpdoc_separation' => true,
            'phpdoc_single_line_var_spacing' => true,
            'phpdoc_summary' => true,
            'phpdoc_to_comment' => true,
            'phpdoc_trim' => true,
            'phpdoc_trim_consecutive_blank_line_separation' => true,
            'phpdoc_types' => true,
            'phpdoc_types_order' => [
                'null_adjustment' => 'always_last',
                'sort_algorithm' => 'none',
            ],
            'phpdoc_var_without_name' => true,
            'return_type_declaration' => true,
            'semicolon_after_instruction' => true,
            'short_scalar_cast' => true,
            'single_blank_line_before_namespace' => true,
            'single_class_element_per_statement' => true,
            'single_line_comment_style' => [
                'comment_types' => ['hash'],
            ],
            'single_line_throw' => true,
            'single_quote' => true,
            'single_trait_insert_per_statement' => true,
            'space_after_semicolon' => [
                'remove_in_empty_for_expressions' => true,
            ],
            'standardize_increment' => true,
            'standardize_not_equals' => true,
            'ternary_operator_spaces' => true,
            'trailing_comma_in_multiline_array' => true,
            'trim_array_spaces' => true,
            'unary_operator_spaces' => true,
            'whitespace_after_comma_in_array' => true,
            'yoda_style' => true,
        ],
        '@Symfony:risky' => [
            'dir_constant' => true,
            'ereg_to_preg' => true,
            'error_suppression' => true,
            'fopen_flag_order' => true,
            'fopen_flags' => ['b_mode' => false],
            'function_to_constant' => [
                'functions' => [
                    'get_called_class',
                    'get_class',
                    'get_class_this',
                    'php_sapi_name',
                    'phpversion',
                    'pi',
                ],
            ],
            'implode_call' => true,
            'is_null' => true,
            'modernize_types_casting' => true,
            'native_constant_invocation' => [
                'fix_built_in' => false,
                'include' => [
                    'DIRECTORY_SEPARATOR',
                    'PHP_SAPI',
                    'PHP_VERSION_ID',
                ],
                'scope' => 'namespaced',
            ],
            'native_function_invocation' => [
                'include' => [NativeFunctionInvocationFixer::SET_COMPILER_OPTIMIZED],
                'scope' => 'namespaced',
                'strict' => true,
            ],
            'no_alias_functions' => true,
            'no_homoglyph_names' => true,
            'no_unneeded_final_method' => true,
            'non_printable_character' => true,
            'php_unit_construct' => true,
            'php_unit_mock_short_will_return' => true,
            'psr4' => true,
            'self_accessor' => true,
            'set_type_to_cast' => true,
        ],
        '@PhpCsFixer' => [
            '@Symfony' => true,
            'align_multiline_comment' => true,
            'array_indentation' => true,
            'blank_line_before_statement' => true,
            'combine_consecutive_issets' => true,
            'combine_consecutive_unsets' => true,
            'compact_nullable_typehint' => true,
            'escape_implicit_backslashes' => true,
            'explicit_indirect_variable' => true,
            'explicit_string_variable' => true,
            'fully_qualified_strict_types' => true,
            'heredoc_to_nowdoc' => true,
            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
            'method_chaining_indentation' => true,
            'multiline_comment_opening_closing' => true,
            'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
            'no_alternative_syntax' => true,
            'no_binary_string' => true,
            'no_extra_blank_lines' => ['tokens' => [
                'break',
                'continue',
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ]],
            'no_null_property_initialization' => true,
            'no_short_echo_tag' => true,
            'no_superfluous_elseif' => true,
            'no_unset_cast' => true,
            'no_useless_else' => true,
            'no_useless_return' => true,
            'ordered_class_elements' => true,
            'php_unit_internal_class' => true,
            'php_unit_method_casing' => true,
            'php_unit_ordered_covers' => true,
            'php_unit_test_class_requires_covers' => true,
            'phpdoc_add_missing_param_annotation' => true,
            'phpdoc_no_empty_return' => true,
            'phpdoc_order' => true,
            'phpdoc_types_order' => true,
            'phpdoc_var_annotation_correct_order' => true,
            'protected_to_private' => true,
            'return_assignment' => true,
            'simple_to_complex_string_variable' => true,
            'single_line_comment_style' => true,
            'single_line_throw' => false,
        ],
        '@PhpCsFixer:risky' => [
            '@Symfony:risky' => true,
            'comment_to_phpdoc' => true,
            'final_internal_class' => true,
            'logical_operators' => true,
            'no_unreachable_default_argument_value' => true,
            'no_unset_on_property' => true,
            'php_unit_set_up_tear_down_visibility' => true,
            'php_unit_strict' => true,
            'php_unit_test_annotation' => true,
            'php_unit_test_case_static_method_calls' => true,
            'strict_comparison' => true,
            'strict_param' => true,
            'string_line_ending' => true,
        ],
        '@DoctrineAnnotation' => [
            'doctrine_annotation_array_assignment' => [
                'operator' => ':',
            ],
            'doctrine_annotation_braces' => true,
            'doctrine_annotation_indentation' => true,
            'doctrine_annotation_spaces' => [
                'before_array_assignments_colon' => false,
            ],
        ],
        '@PHP56Migration' => [],
        '@PHP56Migration:risky' => [
            'pow_to_exponentiation' => true,
        ],
        '@PHP70Migration' => [
            '@PHP56Migration' => true,
            'ternary_to_null_coalescing' => true,
        ],
        '@PHP70Migration:risky' => [
            '@PHP56Migration:risky' => true,
            'combine_nested_dirname' => true,
            'declare_strict_types' => true,
            'non_printable_character' => [
                'use_escape_sequences_in_strings' => true,
            ],
            'random_api_migration' => ['replacements' => [
                'mt_rand' => 'random_int',
                'rand' => 'random_int',
            ]],
        ],
        '@PHP71Migration' => [
            '@PHP70Migration' => true,
            'visibility_required' => ['elements' => [
                'const',
                'method',
                'property',
            ]],
        ],
        '@PHP71Migration:risky' => [
            '@PHP70Migration:risky' => true,
            'void_return' => true,
        ],
        '@PHP73Migration' => [
            '@PHP71Migration' => true,
            'heredoc_indentation' => true,
        ],
        '@PHPUnit30Migration:risky' => [
            'php_unit_dedicate_assert' => ['target' => PhpUnitTargetVersion::VERSION_3_0],
        ],
        '@PHPUnit32Migration:risky' => [
            '@PHPUnit30Migration:risky' => true,
            'php_unit_no_expectation_annotation' => ['target' => PhpUnitTargetVersion::VERSION_3_2],
        ],
        '@PHPUnit35Migration:risky' => [
            '@PHPUnit32Migration:risky' => true,
            'php_unit_dedicate_assert' => ['target' => PhpUnitTargetVersion::VERSION_3_5],
        ],
        '@PHPUnit43Migration:risky' => [
            '@PHPUnit35Migration:risky' => true,
            'php_unit_no_expectation_annotation' => ['target' => PhpUnitTargetVersion::VERSION_4_3],
        ],
        '@PHPUnit48Migration:risky' => [
            '@PHPUnit43Migration:risky' => true,
            'php_unit_namespaced' => ['target' => PhpUnitTargetVersion::VERSION_4_8],
        ],
        '@PHPUnit50Migration:risky' => [
            '@PHPUnit48Migration:risky' => true,
            'php_unit_dedicate_assert' => true,
        ],
        '@PHPUnit52Migration:risky' => [
            '@PHPUnit50Migration:risky' => true,
            'php_unit_expectation' => ['target' => PhpUnitTargetVersion::VERSION_5_2],
        ],
        '@PHPUnit54Migration:risky' => [
            '@PHPUnit52Migration:risky' => true,
            'php_unit_mock' => ['target' => PhpUnitTargetVersion::VERSION_5_4],
        ],
        '@PHPUnit55Migration:risky' => [
            '@PHPUnit54Migration:risky' => true,
            'php_unit_mock' => ['target' => PhpUnitTargetVersion::VERSION_5_5],
        ],
        '@PHPUnit56Migration:risky' => [
            '@PHPUnit55Migration:risky' => true,
            'php_unit_dedicate_assert' => ['target' => PhpUnitTargetVersion::VERSION_5_6],
            'php_unit_expectation' => ['target' => PhpUnitTargetVersion::VERSION_5_6],
        ],
        '@PHPUnit57Migration:risky' => [
            '@PHPUnit56Migration:risky' => true,
            'php_unit_namespaced' => ['target' => PhpUnitTargetVersion::VERSION_5_7],
        ],
        '@PHPUnit60Migration:risky' => [
            '@PHPUnit57Migration:risky' => true,
            'php_unit_namespaced' => ['target' => PhpUnitTargetVersion::VERSION_6_0],
        ],
        '@PHPUnit75Migration:risky' => [
            '@PHPUnit60Migration:risky' => true,
            'php_unit_dedicate_assert_internal_type' => ['target' => PhpUnitTargetVersion::VERSION_7_5],
        ],
    ];

    /**
     * Set that was used to generate group of rules.
     *
     * The key is name of rule or set, value is bool if the rule/set should be used.
     *
     * @var array
     */
    private $set;

    /**
     * Group of rules generated from input set.
     *
     * The key is name of rule, value is bool if the rule/set should be used.
     * The key must not point to any set.
     *
     * @var array
     */
    private $rules;

    public function __construct(array $set = [])
    {
        foreach ($set as $key => $value) {
            if (\is_int($key)) {
                throw new \InvalidArgumentException(sprintf('Missing value for "%s" rule/set.', $value));
            }
        }

        $this->set = $set;
        $this->resolveSet();
    }

    public static function create(array $set = [])
    {
        return new self($set);
    }

    /**
     * {@inheritdoc}
     */
    public function hasRule($rule)
    {
        return \array_key_exists($rule, $this->rules);
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleConfiguration($rule)
    {
        if (!$this->hasRule($rule)) {
            throw new \InvalidArgumentException(sprintf('Rule "%s" is not in the set.', $rule));
        }

        if (true === $this->rules[$rule]) {
            return null;
        }

        return $this->rules[$rule];
    }

    /**
     * {@inheritdoc}
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetDefinitionNames()
    {
        return array_keys($this->setDefinitions);
    }

    /**
     * @param string $name name of set
     *
     * @return array
     */
    private function getSetDefinition($name)
    {
        if (!isset($this->setDefinitions[$name])) {
            throw new \InvalidArgumentException(sprintf('Set "%s" does not exist.', $name));
        }

        return $this->setDefinitions[$name];
    }

    /**
     * Resolve input set into group of rules.
     *
     * @return $this
     */
    private function resolveSet()
    {
        $rules = $this->set;
        $resolvedRules = [];

        // expand sets
        foreach ($rules as $name => $value) {
            if ('@' === $name[0]) {
                if (!\is_bool($value)) {
                    throw new \UnexpectedValueException(sprintf('Nested rule set "%s" configuration must be a boolean.', $name));
                }

                $set = $this->resolveSubset($name, $value);
                $resolvedRules = array_merge($resolvedRules, $set);
            } else {
                $resolvedRules[$name] = $value;
            }
        }

        // filter out all resolvedRules that are off
        $resolvedRules = array_filter($resolvedRules);

        $this->rules = $resolvedRules;

        return $this;
    }

    /**
     * Resolve set rules as part of another set.
     *
     * If set value is false then disable all fixers in set,
     * if not then get value from set item.
     *
     * @param string $setName
     * @param bool   $setValue
     *
     * @return array
     */
    private function resolveSubset($setName, $setValue)
    {
        $rules = $this->getSetDefinition($setName);
        foreach ($rules as $name => $value) {
            if ('@' === $name[0]) {
                $set = $this->resolveSubset($name, $setValue);
                unset($rules[$name]);
                $rules = array_merge($rules, $set);
            } elseif (!$setValue) {
                $rules[$name] = false;
            } else {
                $rules[$name] = $value;
            }
        }

        return $rules;
    }
}
