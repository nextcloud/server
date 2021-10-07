<?php

namespace Bamarni\Composer\Bin;

use Composer\Composer;

final class Config
{
    private $config;

    public function __construct(Composer $composer)
    {
        $extra = $composer->getPackage()->getExtra();
        $this->config = array_merge(
            [
                'bin-links' => true,
                'target-directory' => 'vendor-bin',
            ],
            isset($extra['bamarni-bin']) ? $extra['bamarni-bin'] : []
        );
    }

    /**
     * @return bool
     */
    public function binLinksAreEnabled()
    {
        return true === $this->config['bin-links'];
    }

    /**
     * @return string
     */
    public function getTargetDirectory()
    {
        return $this->config['target-directory'];
    }
}
