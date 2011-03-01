<?php
/**
 * Smarty Internal Plugin Templateparser Parsetrees
 *
 * These are classes to build parsetrees in the template parser
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Thue Kristensen
 * @author Uwe Tews
 */

abstract class _smarty_parsetree {
  abstract public function to_smarty_php();
}

/**
 * A complete smarty tag.
 */
class _smarty_tag extends _smarty_parsetree
{
    public $parser;
    public $data;
    public $saved_block_nesting;
    function __construct($parser, $data)
    {
        $this->parser = $parser;
        $this->data = $data;
        $this->saved_block_nesting = $parser->block_nesting_level;
    }

    public function to_smarty_php()
    {
        return $this->data;
    }

    public function assign_to_var()
    {
        $var = sprintf('$_tmp%d', ++$this->parser->prefix_number);
        $this->parser->compiler->prefix_code[] = sprintf('<?php ob_start();?>%s<?php %s=ob_get_clean();?>',
            $this->data, $var);
        return $var;
    }
}

/**
 * Code fragment inside a tag.
 */
class _smarty_code extends _smarty_parsetree {
    public $parser;
    public $data;
    function __construct($parser, $data)
    {
        $this->parser = $parser;
        $this->data = $data;
    }

    public function to_smarty_php()
    {
        return sprintf("(%s)", $this->data);
    }
}

/**
 * Double quoted string inside a tag.
 */
class _smarty_doublequoted extends _smarty_parsetree {
    public $parser;
    public $subtrees = Array();
    function __construct($parser, _smarty_parsetree $subtree)
    {
        $this->parser = $parser;
        $this->subtrees[] = $subtree;
        if ($subtree instanceof _smarty_tag) {
            $this->parser->block_nesting_level = count($this->parser->compiler->_tag_stack);
        }
    }

    function append_subtree(_smarty_parsetree $subtree)
    {
        $last_subtree = count($this->subtrees)-1;
        if ($last_subtree >= 0 && $this->subtrees[$last_subtree] instanceof _smarty_tag && $this->subtrees[$last_subtree]->saved_block_nesting < $this->parser->block_nesting_level) {
            if ($subtree instanceof _smarty_code) {
                $this->subtrees[$last_subtree]->data .= '<?php echo ' . $subtree->data . ';?>';
            } elseif ($subtree instanceof _smarty_dq_content) {
                $this->subtrees[$last_subtree]->data .= '<?php echo "' . $subtree->data . '";?>';
            } else {
                $this->subtrees[$last_subtree]->data .= $subtree->data;
            }
        } else {
            $this->subtrees[] = $subtree;
        }
        if ($subtree instanceof _smarty_tag) {
            $this->parser->block_nesting_level = count($this->parser->compiler->_tag_stack);
        }
    }

    public function to_smarty_php()
    {
        $code = '';
        foreach ($this->subtrees as $subtree) {
            if ($code !== "") {
                $code .= ".";
            }
            if ($subtree instanceof _smarty_tag) {
                $more_php = $subtree->assign_to_var();
            } else {
                $more_php = $subtree->to_smarty_php();
            }

            $code .= $more_php;

            if (!$subtree instanceof _smarty_dq_content) {
                $this->parser->compiler->has_variable_string = true;
            }
        }
        return $code;
    }
}

/**
 * Raw chars as part of a double quoted string.
 */
class _smarty_dq_content extends _smarty_parsetree {
    public $data;
    function __construct($parser, $data)
    {
        $this->parser = $parser;
        $this->data = $data;
    }

    public function to_smarty_php()
    {
        return '"' . $this->data . '"';
    }
}

/**
 * Template element
 */
class _smarty_template_buffer extends _smarty_parsetree {
    public $subtrees = Array();
    function __construct($parser)
    {
        $this->parser = $parser;
    }

    function append_subtree(_smarty_parsetree $subtree)
    {
        $this->subtrees[] = $subtree;
    }

    public function to_smarty_php()
    {
        $code = '';
        for ($key = 0, $cnt = count($this->subtrees); $key < $cnt; $key++) {
            if ($key + 2 < $cnt) {
                if ($this->subtrees[$key] instanceof _smarty_linebreak && $this->subtrees[$key + 1] instanceof _smarty_tag && $this->subtrees[$key + 1]->data == '' && $this->subtrees[$key + 2] instanceof _smarty_linebreak) {
                    $key = $key + 1;
                    continue;
                }
                if (substr($this->subtrees[$key]->data, -1) == '<' && $this->subtrees[$key + 1]->data == '' && substr($this->subtrees[$key + 2]->data, -1) == '?') {
                    $key = $key + 2;
                    continue;
                }
            }
            if (substr($code, -1) == '<') {
                $subtree = $this->subtrees[$key]->to_smarty_php();
                if (substr($subtree, 0, 1) == '?') {
                    $code = substr($code, 0, strlen($code)-1) . '<<?php ?>?' . substr($subtree, 1);
                } elseif ($this->parser->asp_tags && substr($subtree, 0, 1) == '%') {
                    $code = substr($code, 0, strlen($code)-1) . '<<?php ?>%' . substr($subtree, 1);
           } else {
                    $code .= $subtree;
                }
                continue;
            }
            if ($this->parser->asp_tags && substr($code, -1) == '%') {
                $subtree = $this->subtrees[$key]->to_smarty_php();
                if (substr($subtree, 0, 1) == '>') {
                    $code = substr($code, 0, strlen($code)-1) . '%<?php ?>>' . substr($subtree, 1);
           } else {
                    $code .= $subtree;
                }
                continue;
            }
            if (substr($code, -1) == '?') {
                $subtree = $this->subtrees[$key]->to_smarty_php();
                if (substr($subtree, 0, 1) == '>') {
                    $code = substr($code, 0, strlen($code)-1) . '?<?php ?>>' . substr($subtree, 1);
           } else {
                    $code .= $subtree;
                }
                continue;
            }
            $code .= $this->subtrees[$key]->to_smarty_php();
        }
        return $code;
    }
}

/**
 * template text
 */
class _smarty_text extends _smarty_parsetree {
    public $data;
    function __construct($parser, $data)
    {
        $this->parser = $parser;
        $this->data = $data;
    }

    public function to_smarty_php()
    {
        return $this->data;
    }
}

/**
 * template linebreaks
 */
class _smarty_linebreak extends _smarty_parsetree {
    public $data;
    function __construct($parser, $data)
    {
        $this->parser = $parser;
        $this->data = $data;
    }

    public function to_smarty_php()
    {
        return $this->data;
    }
}

?>