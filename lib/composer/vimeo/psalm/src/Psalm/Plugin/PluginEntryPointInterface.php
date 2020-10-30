<?php
namespace Psalm\Plugin;

use SimpleXMLElement;

interface PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void;
}
