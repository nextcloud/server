<?php
namespace Psalm\Example\Plugin\ComposerBased;

use Psalm\Plugin;
use SimpleXMLElement;

class PluginEntryPoint implements Plugin\PluginEntryPointInterface
{
    public function __invoke(Plugin\RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/EchoChecker.php';
        $registration->registerHooksFromClass(EchoChecker::class);
    }
}
