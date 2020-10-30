<?php
namespace Psalm\Storage;

class FunctionStorage extends FunctionLikeStorage
{
    /** @var array<string, bool> */
    public $byref_uses = [];
}
