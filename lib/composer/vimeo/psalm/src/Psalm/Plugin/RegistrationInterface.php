<?php
namespace Psalm\Plugin;

interface RegistrationInterface
{
    public function addStubFile(string $file_name): void;

    public function registerHooksFromClass(string $handler): void;
}
