<?php

declare(strict_types=1);

namespace Nextcloud\CodingStandard;

use PhpCsFixer\Config as Base;

class Config extends Base
{

	public function __construct($name = 'default')
	{
		parent::__construct($name);
		$this->setIndent("\t");
	}

	public function getRules()
	{
		return [
			'@PSR1' => true,
			'@PSR2' => true,
			'align_multiline_comment' => true,
			'array_indentation' => true,
			'array_syntax' => [
				'syntax' => 'short',
			],
			'blank_line_after_namespace' => true,
			'blank_line_after_opening_tag' => true,
			'braces' => [
				'position_after_anonymous_constructs' => 'same',
				'position_after_control_structures' => 'same',
				'position_after_functions_and_oop_constructs' => 'same',
			],
			'elseif' => true,
			'encoding' => true,
			'full_opening_tag' => true,
			'function_declaration' => [
				'closure_function_spacing' => 'one',
			],
			'indentation_type' => true,
			'line_ending' => true,
			'lowercase_keywords' => true,
			'method_argument_space' => [],
			'no_closing_tag' => true,
			'no_spaces_after_function_name' => true,
			'no_spaces_inside_parenthesis' => true,
			'no_trailing_whitespace' => true,
			'no_trailing_whitespace_in_comment' => true,
			'no_unused_imports' => true,
			'single_blank_line_at_eof' => true,
			'single_class_element_per_statement' => true,
			'single_import_per_statement' => true,
			'single_line_after_imports' => true,
			'switch_case_space' => true,
			'visibility_required' => [
				'elements' => ['property', 'method', 'const']
			],
		];
	}

}
