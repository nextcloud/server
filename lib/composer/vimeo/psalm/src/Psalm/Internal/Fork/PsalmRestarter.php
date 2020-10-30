<?php
namespace Psalm\Internal\Fork;

use function array_filter;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function preg_replace;

/**
 * @internal
 */
class PsalmRestarter extends \Composer\XdebugHandler\XdebugHandler
{
    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var string[]
     */
    private $disabledExtensions = [];

    public function disableExtension(string $disabledExtension): void
    {
        $this->disabledExtensions[] = $disabledExtension;
    }

    /**
     * @param mixed $isLoaded
     */
    protected function requiresRestart($isLoaded): bool
    {
        $this->required = (bool) array_filter(
            $this->disabledExtensions,
            function (string $extension): bool {
                return extension_loaded($extension);
            }
        );

        return $isLoaded || $this->required;
    }

    /**
     * @param mixed $command
     */
    protected function restart($command): void
    {
        if ($this->required && $this->tmpIni) {
            $regex = '/^\s*(extension\s*=.*(' . implode('|', $this->disabledExtensions) . ').*)$/mi';
            $content = file_get_contents($this->tmpIni);

            $content = preg_replace($regex, ';$1', $content);

            file_put_contents($this->tmpIni, $content);
        }

        /** @psalm-suppress MixedArgument */
        parent::restart($command);
    }
}
