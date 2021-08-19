<?php
namespace JmesPath;

/**
 * Uses an external tree visitor to interpret an AST.
 */
class AstRuntime
{
    private $parser;
    private $interpreter;
    private $cache = [];
    private $cachedCount = 0;

    public function __construct(
        Parser $parser = null,
        callable $fnDispatcher = null
    ) {
        $fnDispatcher = $fnDispatcher ?: FnDispatcher::getInstance();
        $this->interpreter = new TreeInterpreter($fnDispatcher);
        $this->parser = $parser ?: new Parser();
    }

    /**
     * Returns data from the provided input that matches a given JMESPath
     * expression.
     *
     * @param string $expression JMESPath expression to evaluate
     * @param mixed  $data       Data to search. This data should be data that
     *                           is similar to data returned from json_decode
     *                           using associative arrays rather than objects.
     *
     * @return mixed Returns the matching data or null
     */
    public function __invoke($expression, $data)
    {
        if (!isset($this->cache[$expression])) {
            // Clear the AST cache when it hits 1024 entries
            if (++$this->cachedCount > 1024) {
                $this->cache = [];
                $this->cachedCount = 0;
            }
            $this->cache[$expression] = $this->parser->parse($expression);
        }

        return $this->interpreter->visit($this->cache[$expression], $data);
    }
}
