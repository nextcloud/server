<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */
var_dump(1);

/**
 * Checks that each use statement is used.
 */
class phpbb_Sniffs_Namespaces_UnusedUseSniff implements PHP_CodeSniffer_Sniff
{
	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		return array(T_USE);
	}

	protected function check(PHP_CodeSniffer_File $phpcsFile, $found_name, $full_name, $short_name, $line)
	{

		if ($found_name === $full_name)
		{
			$error = 'Either use statement or full name must be used.';
			$phpcsFile->addError($error, $line, 'FullName');
		}

		if ($found_name === $short_name)
		{
			return true;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		if ($this->should_ignore_use($phpcsFile, $stackPtr) === true)
		{
			return;
		}

		$tokens = $phpcsFile->getTokens();

		$class_name_start = $phpcsFile->findNext(array(T_NS_SEPARATOR, T_STRING), ($stackPtr + 1));

		$find = array(
			T_NS_SEPARATOR,
			T_STRING,
			T_WHITESPACE,
		);

		$class_name_end = $phpcsFile->findNext($find, ($stackPtr + 1), null, true);

		$aliasing_as_position = $phpcsFile->findNext(T_AS, $class_name_end, null, false, null, true);
		if ($aliasing_as_position !== false)
		{
			$alias_position = $phpcsFile->findNext(T_STRING, $aliasing_as_position, null, false, null, true);
			$class_name_short = $tokens[$alias_position]['content'];
			$class_name_full = $phpcsFile->getTokensAsString($class_name_start, ($class_name_end - $class_name_start - 1));
		}
		else
		{
			$class_name_full = $phpcsFile->getTokensAsString($class_name_start, ($class_name_end - $class_name_start));
			$class_name_short = $tokens[$class_name_end - 1]['content'];
		}

		$ok = false;

		// Checks in simple statements (new, instanceof and extends)
		foreach (array(T_INSTANCEOF, T_NEW, T_EXTENDS) as $keyword)
		{
			$old_simple_statement = $stackPtr;
			while (($simple_statement = $phpcsFile->findNext($keyword, ($old_simple_statement + 1))) !== false)
			{
				$old_simple_statement = $simple_statement;

				$simple_class_name_start = $phpcsFile->findNext(array(T_NS_SEPARATOR, T_STRING), ($simple_statement + 1));
				$simple_class_name_end = $phpcsFile->findNext($find, ($simple_statement + 1), null, true);

				$simple_class_name = trim($phpcsFile->getTokensAsString($simple_class_name_start, ($simple_class_name_end - $simple_class_name_start)));

				$ok = $this->check($phpcsFile, $simple_class_name, $class_name_full, $class_name_short, $simple_statement) ? true : $ok;
			}
		}

		// Checks paamayim nekudotayim
		$old_paamayim_nekudotayim = $stackPtr;
		while (($paamayim_nekudotayim = $phpcsFile->findNext(T_PAAMAYIM_NEKUDOTAYIM, ($old_paamayim_nekudotayim + 1))) !== false)
		{
			$old_paamayim_nekudotayim = $paamayim_nekudotayim;

			$paamayim_nekudotayim_class_name_start = $phpcsFile->findPrevious($find, $paamayim_nekudotayim - 1, null, true);
			$paamayim_nekudotayim_class_name_end = $paamayim_nekudotayim - 1;

			$paamayim_nekudotayim_class_name = trim($phpcsFile->getTokensAsString($paamayim_nekudotayim_class_name_start + 1, ($paamayim_nekudotayim_class_name_end - $paamayim_nekudotayim_class_name_start)));

			$ok = $this->check($phpcsFile, $paamayim_nekudotayim_class_name, $class_name_full, $class_name_short, $paamayim_nekudotayim) ? true : $ok;
		}

		// Checks in implements
		$old_implements = $stackPtr;
		while (($implements = $phpcsFile->findNext(T_IMPLEMENTS, ($old_implements + 1))) !== false)
		{
			$old_implements = $implements;

			$old_implemented_class = $implements;
			while (($implemented_class = $phpcsFile->findNext(T_STRING, ($old_implemented_class + 1), null, false, null, true)) !== false)
			{
				$old_implemented_class = $implemented_class;

				$implements_class_name_start = $phpcsFile->findNext(array(T_NS_SEPARATOR, T_STRING), ($implemented_class - 1));
				$implements_class_name_end = $phpcsFile->findNext($find, ($implemented_class - 1), null, true);

				$implements_class_name = trim($phpcsFile->getTokensAsString($implements_class_name_start, ($implements_class_name_end - $implements_class_name_start)));

				$ok = $this->check($phpcsFile, $implements_class_name, $class_name_full, $class_name_short, $implements) ? true : $ok;
			}
		}

		$old_docblock = $stackPtr;
		while (($docblock = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, ($old_docblock + 1))) !== false)
		{
			$old_docblock = $docblock;
			$ok = $this->checkDocblock($phpcsFile, $docblock, $tokens, $class_name_full, $class_name_short) ? true : $ok;
		}

		// Checks in type hinting
		$old_function_declaration = $stackPtr;
		while (($function_declaration = $phpcsFile->findNext(T_FUNCTION, ($old_function_declaration + 1))) !== false)
		{
			$old_function_declaration = $function_declaration;

			// Check type hint
			$params = $phpcsFile->getMethodParameters($function_declaration);
			foreach ($params as $param)
			{
				$ok = $this->check($phpcsFile, $param['type_hint'], $class_name_full, $class_name_short, $function_declaration) ? true : $ok;
			}
		}

		// Checks in catch blocks
		$old_catch = $stackPtr;
		while (($catch = $phpcsFile->findNext(T_CATCH, ($old_catch + 1))) !== false)
		{
			$old_catch = $catch;

			$caught_class_name_start = $phpcsFile->findNext(array(T_NS_SEPARATOR, T_STRING), $catch + 1);
			$caught_class_name_end = $phpcsFile->findNext($find, $caught_class_name_start + 1, null, true);

			$caught_class_name = trim($phpcsFile->getTokensAsString($caught_class_name_start, ($caught_class_name_end - $caught_class_name_start)));

			$ok = $this->check($phpcsFile, $caught_class_name, $class_name_full, $class_name_short, $catch) ? true : $ok;
		}

		if (!$ok)
		{
			$error = 'There must not be unused USE statements.';
			$phpcsFile->addError($error, $stackPtr, 'Unused');
		}
	}

	/**
	 * Check if this use statement is part of the namespace block.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token in
	 *                                        the stack passed in $tokens.
	 *
	 * @return bool
	 */
	private function should_ignore_use(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// Ignore USE keywords inside closures.
		$next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
		if ($tokens[$next]['code'] === T_OPEN_PARENTHESIS)
		{
			return true;
		}

		// Ignore USE keywords for traits.
		if ($phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_TRAIT)) === true)
		{
			return true;
		}

		return false;

	}

	/**
	 * @param PHP_CodeSniffer_File $phpcsFile
	 * @param int $field
	 * @param array $tokens
	 * @param string $class_name_full
	 * @param string $class_name_short
	 * @param bool $ok
	 *
	 * @return bool
	 */
	private function checkDocblock(PHP_CodeSniffer_File $phpcsFile, $comment_end, $tokens, $class_name_full, $class_name_short)
	{
		$ok = false;

		$comment_start = $tokens[$comment_end]['comment_opener'];
		foreach ($tokens[$comment_start]['comment_tags'] as $tag)
		{
			if (!in_array($tokens[$tag]['content'], array('@param', '@var', '@return', '@throws'), true))
			{
				continue;
			}

			$classes = $tokens[($tag + 2)]['content'];
			$space = strpos($classes, ' ');
			if ($space !== false)
			{
				$classes = substr($classes, 0, $space);
			}

			$tab = strpos($classes, "\t");
			if ($tab !== false)
			{
				$classes = substr($classes, 0, $tab);
			}

			$classes = explode('|', str_replace('[]', '', $classes));
			foreach ($classes as $class)
			{
				$ok = $this->check($phpcsFile, $class, $class_name_full, $class_name_short, $tokens[$tag + 2]['line']) ? true : $ok;
			}
		}

		return $ok;
	}
}
