<?php

namespace OpenStack\Integration;

interface SampleManagerInterface
{
    public function write($path, array $replacements);

    public function deletePaths();
}
