<?php
namespace Psalm\SourceControl;

abstract class SourceControlInfo
{
    abstract public function toArray() : array;
}
