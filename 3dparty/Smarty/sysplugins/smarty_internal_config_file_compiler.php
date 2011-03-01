<?php

/**
 * Smarty Internal Plugin Config File Compiler
 *
 * This is the config file compiler class. It calls the lexer and parser to
 * perform the compiling.
 *
 * @package Smarty
 * @subpackage Config
 * @author Uwe Tews
 */

/**
 * Main config file compiler class
 */
class Smarty_Internal_Config_File_Compiler {
    /**
     * Initialize compiler
     */
    public function __construct($smarty)
    {
        $this->smarty = $smarty;
        // get required plugins
        $this->smarty->loadPlugin('Smarty_Internal_Configfilelexer');
		$this->smarty->loadPlugin('Smarty_Internal_Configfileparser');
        $this->config_data['sections'] = array();
        $this->config_data['vars'] = array();
    }

    /**
     * Methode to compile a Smarty template
     *
     * @param  $template template object to compile
     * @return bool true if compiling succeeded, false if it failed
     */
    public function compileSource($config)
    {
        /* here is where the compiling takes place. Smarty
       tags in the templates are replaces with PHP code,
       then written to compiled files. */
        $this->config = $config;
        // get config file source
        $_content = $config->getConfigSource() . "\n";
        // on empty template just return
        if ($_content == '') {
            return true;
        }
        // init the lexer/parser to compile the config file
        $lex = new Smarty_Internal_Configfilelexer($_content, $this->smarty);
        $parser = new Smarty_Internal_Configfileparser($lex, $this);
        if (isset($this->smarty->_parserdebug)) $parser->PrintTrace();
        // get tokens from lexer and parse them
        while ($lex->yylex()) {
            if (isset($this->smarty->_parserdebug)) echo "<br>Parsing  {$parser->yyTokenName[$lex->token]} Token {$lex->value} Line {$lex->line} \n";
            $parser->doParse($lex->token, $lex->value);
        }
        // finish parsing process
        $parser->doParse(0, 0);
        $config->compiled_config = '<?php $_config_vars = ' . var_export($this->config_data, true) . '; ?>';
    }
    /**
     * display compiler error messages without dying
     *
     * If parameter $args is empty it is a parser detected syntax error.
     * In this case the parser is called to obtain information about exspected tokens.
     *
     * If parameter $args contains a string this is used as error message
     *
     * @todo output exact position of parse error in source line
     * @param  $args string individual error message or null
     */
    public function trigger_config_file_error($args = null)
    {
        $this->lex = Smarty_Internal_Configfilelexer::instance();
        $this->parser = Smarty_Internal_Configfileparser::instance();
        // get template source line which has error
        $line = $this->lex->line;
        if (isset($args)) {
            // $line--;
        }
        $match = preg_split("/\n/", $this->lex->data);
        $error_text = "Syntax error in config file '{$this->config->getConfigFilepath()}' on line {$line} '{$match[$line-1]}' ";
        if (isset($args)) {
            // individual error message
            $error_text .= $args;
        } else {
            // exspected token from parser
            foreach ($this->parser->yy_get_expected_tokens($this->parser->yymajor) as $token) {
                $exp_token = $this->parser->yyTokenName[$token];
                if (isset($this->lex->smarty_token_names[$exp_token])) {
                    // token type from lexer
                    $expect[] = '"' . $this->lex->smarty_token_names[$exp_token] . '"';
                } else {
                    // otherwise internal token name
                    $expect[] = $this->parser->yyTokenName[$token];
                }
            }
            // output parser error message
            $error_text .= ' - Unexpected "' . $this->lex->value . '", expected one of: ' . implode(' , ', $expect);
        }
        throw new SmartyCompilerException($error_text);
    }
}

?>