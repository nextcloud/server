<?php
$finder = PhpCsFixer\Finder::create()
	->exclude('vendor')
	->in(__DIR__)
;
return PhpCsFixer\Config::create()
	->setRules([
		'@PSR2' => true,
		'array_syntax' => ['syntax' => 'short'],
		'braces' => ['position_after_functions_and_oop_constructs' => 'same'],
		'binary_operator_spaces' => ['align_double_arrow' => true, 'align_equals' => false],
	])
	->setIndent("\t")
	->setFinder($finder)
	;
