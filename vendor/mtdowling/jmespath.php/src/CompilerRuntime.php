<?php
namespace JmesPath;

/**
 * Compiles JMESPath expressions to PHP source code and executes it.
 *
 * JMESPath file names are stored in the cache directory using the following
 * logic to determine the filename:
 *
 * 1. Start with the string "jmespath_"
 * 2. Append the MD5 checksum of the expression.
 * 3. Append ".php"
 */
class CompilerRuntime
{
    private $parser;
    private $compiler;
    private $cacheDir;
    private $interpreter;

    /**
     * @param string|null $dir Directory used to store compiled PHP files.
     * @param Parser|null $parser JMESPath parser to utilize
     * @throws \RuntimeException if the cache directory cannot be created
     */
    public function __construct($dir = null, ?Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
        $this->compiler = new TreeCompiler();
        $dir = $dir ?: sys_get_temp_dir();

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Unable to create cache directory: $dir");
        }

        $this->cacheDir = realpath($dir);
        $this->interpreter = new TreeInterpreter();
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
     * @throws \RuntimeException
     */
    public function __invoke($expression, $data)
    {
        $functionName = 'jmespath_' . md5($expression);

        if (!function_exists($functionName)) {
            $filename = "{$this->cacheDir}/{$functionName}.php";
            if (!file_exists($filename)) {
                $this->compile($filename, $expression, $functionName);
            }
            require $filename;
        }

        return $functionName($this->interpreter, $data);
    }

    private function compile($filename, $expression, $functionName)
    {
        $code = $this->compiler->visit(
            $this->parser->parse($expression),
            $functionName,
            $expression
        );

        if (!file_put_contents($filename, $code)) {
            throw new \RuntimeException(sprintf(
                'Unable to write the compiled PHP code to: %s (%s)',
                $filename,
                var_export(error_get_last(), true)
            ));
        }
    }
}
