<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/sfYamlInline.php');

if (!defined('PREG_BAD_UTF8_OFFSET_ERROR'))
{
  define('PREG_BAD_UTF8_OFFSET_ERROR', 5);
}

/**
 * sfYamlParser parses YAML strings to convert them to PHP arrays.
 *
 * @package    symfony
 * @subpackage yaml
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfYamlParser.class.php 10832 2008-08-13 07:46:08Z fabien $
 */
class sfYamlParser
{
  protected
    $offset        = 0,
    $lines         = array(),
    $currentLineNb = -1,
    $currentLine   = '',
    $refs          = array();

  /**
   * Constructor
   *
   * @param integer $offset The offset of YAML document (used for line numbers in error messages)
   */
  public function __construct($offset = 0)
  {
    $this->offset = $offset;
  }

  /**
   * Parses a YAML string to a PHP value.
   *
   * @param  string $value A YAML string
   *
   * @return mixed  A PHP value
   *
   * @throws InvalidArgumentException If the YAML is not valid
   */
  public function parse($value)
  {
    $value = str_replace("\t", '  ', $value); // Convert tabs to spaces.

    $this->currentLineNb = -1;
    $this->currentLine = '';
    $this->lines = explode("\n", $this->cleanup($value));

    if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2)
    {
      $mbEncoding = mb_internal_encoding();
      mb_internal_encoding('ASCII');
    }

    $data = array();
    while ($this->moveToNextLine())
    {
      if ($this->isCurrentLineEmpty())
      {
        continue;
      }

      // tab?
      if (preg_match('#^\t+#', $this->currentLine))
      {
        throw new InvalidArgumentException(sprintf('A YAML file cannot contain tabs as indentation at line %d (%s).', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }

      $isRef = $isInPlace = $isProcessed = false;
      if (preg_match('#^\-((?P<leadspaces>\s+)(?P<value>.+?))?\s*$#', $this->currentLine, $values))
      {
        if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#', $values['value'], $matches))
        {
          $isRef = $matches['ref'];
          $values['value'] = $matches['value'];
        }

        // array
        if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#'))
        {
          $c = $this->getRealCurrentLineNb() + 1;
          $parser = new sfYamlParser($c);
          $parser->refs =& $this->refs;
          $data[] = $parser->parse($this->getNextEmbedBlock());
        }
        else
        {
          if (isset($values['leadspaces'])
            && ' ' == $values['leadspaces']
            && preg_match('#^(?P<key>'.sfYamlInline::REGEX_QUOTED_STRING.'|[^ \'"\{].*?) *\:(\s+(?P<value>.+?))?\s*$#', $values['value'], $matches))
          {
            // this is a compact notation element, add to next block and parse
            $c = $this->getRealCurrentLineNb();
            $parser = new sfYamlParser($c);
            $parser->refs =& $this->refs;

            $block = $values['value'];
            if (!$this->isNextLineIndented())
            {
              $block .= "\n".$this->getNextEmbedBlock($this->getCurrentLineIndentation() + 2);
            }

            $data[] = $parser->parse($block);
          }
          else
          {
            $data[] = $this->parseValue($values['value']);
          }
        }
      }
      else if (preg_match('#^(?P<key>'.sfYamlInline::REGEX_QUOTED_STRING.'|[^ \'"].*?) *\:(\s+(?P<value>.+?))?\s*$#', $this->currentLine, $values))
      {
        $key = sfYamlInline::parseScalar($values['key']);

        if ('<<' === $key)
        {
          if (isset($values['value']) && '*' === substr($values['value'], 0, 1))
          {
            $isInPlace = substr($values['value'], 1);
            if (!array_key_exists($isInPlace, $this->refs))
            {
              throw new InvalidArgumentException(sprintf('Reference "%s" does not exist at line %s (%s).', $isInPlace, $this->getRealCurrentLineNb() + 1, $this->currentLine));
            }
          }
          else
          {
            if (isset($values['value']) && $values['value'] !== '')
            {
              $value = $values['value'];
            }
            else
            {
              $value = $this->getNextEmbedBlock();
            }
            $c = $this->getRealCurrentLineNb() + 1;
            $parser = new sfYamlParser($c);
            $parser->refs =& $this->refs;
            $parsed = $parser->parse($value);

            $merged = array();
            if (!is_array($parsed))
            {
              throw new InvalidArgumentException(sprintf("YAML merge keys used with a scalar value instead of an array at line %s (%s)", $this->getRealCurrentLineNb() + 1, $this->currentLine));
            }
            else if (isset($parsed[0]))
            {
              // Numeric array, merge individual elements
              foreach (array_reverse($parsed) as $parsedItem)
              {
                if (!is_array($parsedItem))
                {
                  throw new InvalidArgumentException(sprintf("Merge items must be arrays at line %s (%s).", $this->getRealCurrentLineNb() + 1, $parsedItem));
                }
                $merged = array_merge($parsedItem, $merged);
              }
            }
            else
            {
              // Associative array, merge
              $merged = array_merge($merge, $parsed);
            }

            $isProcessed = $merged;
          }
        }
        else if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#', $values['value'], $matches))
        {
          $isRef = $matches['ref'];
          $values['value'] = $matches['value'];
        }

        if ($isProcessed)
        {
          // Merge keys
          $data = $isProcessed;
        }
        // hash
        else if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#'))
        {
          // if next line is less indented or equal, then it means that the current value is null
          if ($this->isNextLineIndented())
          {
            $data[$key] = null;
          }
          else
          {
            $c = $this->getRealCurrentLineNb() + 1;
            $parser = new sfYamlParser($c);
            $parser->refs =& $this->refs;
            $data[$key] = $parser->parse($this->getNextEmbedBlock());
          }
        }
        else
        {
          if ($isInPlace)
          {
            $data = $this->refs[$isInPlace];
          }
          else
          {
            $data[$key] = $this->parseValue($values['value']);
          }
        }
      }
      else
      {
        // 1-liner followed by newline
        if (2 == count($this->lines) && empty($this->lines[1]))
        {
          $value = sfYamlInline::load($this->lines[0]);
          if (is_array($value))
          {
            $first = reset($value);
            if ('*' === substr($first, 0, 1))
            {
              $data = array();
              foreach ($value as $alias)
              {
                $data[] = $this->refs[substr($alias, 1)];
              }
              $value = $data;
            }
          }

          if (isset($mbEncoding))
          {
            mb_internal_encoding($mbEncoding);
          }

          return $value;
        }

        switch (preg_last_error())
        {
          case PREG_INTERNAL_ERROR:
            $error = 'Internal PCRE error on line';
            break;
          case PREG_BACKTRACK_LIMIT_ERROR:
            $error = 'pcre.backtrack_limit reached on line';
            break;
          case PREG_RECURSION_LIMIT_ERROR:
            $error = 'pcre.recursion_limit reached on line';
            break;
          case PREG_BAD_UTF8_ERROR:
            $error = 'Malformed UTF-8 data on line';
            break;
          case PREG_BAD_UTF8_OFFSET_ERROR:
            $error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point on line';
            break;
          default:
            $error = 'Unable to parse line';
        }

        throw new InvalidArgumentException(sprintf('%s %d (%s).', $error, $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }

      if ($isRef)
      {
        $this->refs[$isRef] = end($data);
      }
    }

    if (isset($mbEncoding))
    {
      mb_internal_encoding($mbEncoding);
    }

    return empty($data) ? null : $data;
  }

  /**
   * Returns the current line number (takes the offset into account).
   *
   * @return integer The current line number
   */
  protected function getRealCurrentLineNb()
  {
    return $this->currentLineNb + $this->offset;
  }

  /**
   * Returns the current line indentation.
   *
   * @return integer The current line indentation
   */
  protected function getCurrentLineIndentation()
  {
    return strlen($this->currentLine) - strlen(ltrim($this->currentLine, ' '));
  }

  /**
   * Returns the next embed block of YAML.
   *
   * @param integer $indentation The indent level at which the block is to be read, or null for default
   *
   * @return string A YAML string
   */
  protected function getNextEmbedBlock($indentation = null)
  {
    $this->moveToNextLine();

    if (null === $indentation)
    {
      $newIndent = $this->getCurrentLineIndentation();

      if (!$this->isCurrentLineEmpty() && 0 == $newIndent)
      {
        throw new InvalidArgumentException(sprintf('Indentation problem at line %d (%s)', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
    }
    else
    {
      $newIndent = $indentation;
    }

    $data = array(substr($this->currentLine, $newIndent));

    while ($this->moveToNextLine())
    {
      if ($this->isCurrentLineEmpty())
      {
        if ($this->isCurrentLineBlank())
        {
          $data[] = substr($this->currentLine, $newIndent);
        }

        continue;
      }

      $indent = $this->getCurrentLineIndentation();

      if (preg_match('#^(?P<text> *)$#', $this->currentLine, $match))
      {
        // empty line
        $data[] = $match['text'];
      }
      else if ($indent >= $newIndent)
      {
        $data[] = substr($this->currentLine, $newIndent);
      }
      else if (0 == $indent)
      {
        $this->moveToPreviousLine();

        break;
      }
      else
      {
        throw new InvalidArgumentException(sprintf('Indentation problem at line %d (%s)', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
    }

    return implode("\n", $data);
  }

  /**
   * Moves the parser to the next line.
   */
  protected function moveToNextLine()
  {
    if ($this->currentLineNb >= count($this->lines) - 1)
    {
      return false;
    }

    $this->currentLine = $this->lines[++$this->currentLineNb];

    return true;
  }

  /**
   * Moves the parser to the previous line.
   */
  protected function moveToPreviousLine()
  {
    $this->currentLine = $this->lines[--$this->currentLineNb];
  }

  /**
   * Parses a YAML value.
   *
   * @param  string $value A YAML value
   *
   * @return mixed  A PHP value
   */
  protected function parseValue($value)
  {
    if ('*' === substr($value, 0, 1))
    {
      if (false !== $pos = strpos($value, '#'))
      {
        $value = substr($value, 1, $pos - 2);
      }
      else
      {
        $value = substr($value, 1);
      }

      if (!array_key_exists($value, $this->refs))
      {
        throw new InvalidArgumentException(sprintf('Reference "%s" does not exist (%s).', $value, $this->currentLine));
      }
      return $this->refs[$value];
    }

    if (preg_match('/^(?P<separator>\||>)(?P<modifiers>\+|\-|\d+|\+\d+|\-\d+|\d+\+|\d+\-)?(?P<comments> +#.*)?$/', $value, $matches))
    {
      $modifiers = isset($matches['modifiers']) ? $matches['modifiers'] : '';

      return $this->parseFoldedScalar($matches['separator'], preg_replace('#\d+#', '', $modifiers), intval(abs($modifiers)));
    }
    else
    {
      return sfYamlInline::load($value);
    }
  }

  /**
   * Parses a folded scalar.
   *
   * @param  string  $separator   The separator that was used to begin this folded scalar (| or >)
   * @param  string  $indicator   The indicator that was used to begin this folded scalar (+ or -)
   * @param  integer $indentation The indentation that was used to begin this folded scalar
   *
   * @return string  The text value
   */
  protected function parseFoldedScalar($separator, $indicator = '', $indentation = 0)
  {
    $separator = '|' == $separator ? "\n" : ' ';
    $text = '';

    $notEOF = $this->moveToNextLine();

    while ($notEOF && $this->isCurrentLineBlank())
    {
      $text .= "\n";

      $notEOF = $this->moveToNextLine();
    }

    if (!$notEOF)
    {
      return '';
    }

    if (!preg_match('#^(?P<indent>'.($indentation ? str_repeat(' ', $indentation) : ' +').')(?P<text>.*)$#', $this->currentLine, $matches))
    {
      $this->moveToPreviousLine();

      return '';
    }

    $textIndent = $matches['indent'];
    $previousIndent = 0;

    $text .= $matches['text'].$separator;
    while ($this->currentLineNb + 1 < count($this->lines))
    {
      $this->moveToNextLine();

      if (preg_match('#^(?P<indent> {'.strlen($textIndent).',})(?P<text>.+)$#', $this->currentLine, $matches))
      {
        if (' ' == $separator && $previousIndent != $matches['indent'])
        {
          $text = substr($text, 0, -1)."\n";
        }
        $previousIndent = $matches['indent'];

        $text .= str_repeat(' ', $diff = strlen($matches['indent']) - strlen($textIndent)).$matches['text'].($diff ? "\n" : $separator);
      }
      else if (preg_match('#^(?P<text> *)$#', $this->currentLine, $matches))
      {
        $text .= preg_replace('#^ {1,'.strlen($textIndent).'}#', '', $matches['text'])."\n";
      }
      else
      {
        $this->moveToPreviousLine();

        break;
      }
    }

    if (' ' == $separator)
    {
      // replace last separator by a newline
      $text = preg_replace('/ (\n*)$/', "\n$1", $text);
    }

    switch ($indicator)
    {
      case '':
        $text = preg_replace('#\n+$#s', "\n", $text);
        break;
      case '+':
        break;
      case '-':
        $text = preg_replace('#\n+$#s', '', $text);
        break;
    }

    return $text;
  }

  /**
   * Returns true if the next line is indented.
   *
   * @return Boolean Returns true if the next line is indented, false otherwise
   */
  protected function isNextLineIndented()
  {
    $currentIndentation = $this->getCurrentLineIndentation();
    $notEOF = $this->moveToNextLine();

    while ($notEOF && $this->isCurrentLineEmpty())
    {
      $notEOF = $this->moveToNextLine();
    }

    if (false === $notEOF)
    {
      return false;
    }

    $ret = false;
    if ($this->getCurrentLineIndentation() <= $currentIndentation)
    {
      $ret = true;
    }

    $this->moveToPreviousLine();

    return $ret;
  }

  /**
   * Returns true if the current line is blank or if it is a comment line.
   *
   * @return Boolean Returns true if the current line is empty or if it is a comment line, false otherwise
   */
  protected function isCurrentLineEmpty()
  {
    return $this->isCurrentLineBlank() || $this->isCurrentLineComment();
  }

  /**
   * Returns true if the current line is blank.
   *
   * @return Boolean Returns true if the current line is blank, false otherwise
   */
  protected function isCurrentLineBlank()
  {
    return '' == trim($this->currentLine, ' ');
  }

  /**
   * Returns true if the current line is a comment line.
   *
   * @return Boolean Returns true if the current line is a comment line, false otherwise
   */
  protected function isCurrentLineComment()
  {
    //checking explicitly the first char of the trim is faster than loops or strpos
    $ltrimmedLine = ltrim($this->currentLine, ' ');
    return $ltrimmedLine[0] === '#';
  }

  /**
   * Cleanups a YAML string to be parsed.
   *
   * @param  string $value The input YAML string
   *
   * @return string A cleaned up YAML string
   */
  protected function cleanup($value)
  {
    $value = str_replace(array("\r\n", "\r"), "\n", $value);

    if (!preg_match("#\n$#", $value))
    {
      $value .= "\n";
    }

    // strip YAML header
    $count = 0;
    $value = preg_replace('#^\%YAML[: ][\d\.]+.*\n#s', '', $value, -1, $count);
    $this->offset += $count;

    // remove leading comments and/or ---
    $trimmedValue = preg_replace('#^((\#.*?\n)|(\-\-\-.*?\n))*#s', '', $value, -1, $count);
    if ($count == 1)
    {
      // items have been removed, update the offset
      $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
      $value = $trimmedValue;
    }

    return $value;
  }
}
