<?php
namespace Psalm;

interface FileSource
{
    public function getFileName(): string;

    public function getFilePath(): string;

    public function getRootFileName(): string;

    public function getRootFilePath(): string;

    public function getAliases(): Aliases;
}
