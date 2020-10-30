<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Console\SelfUpdate;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

/**
 * @internal
 */
final class NewVersionChecker implements NewVersionCheckerInterface
{
    /**
     * @var GithubClientInterface
     */
    private $githubClient;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @var null|string[]
     */
    private $availableVersions;

    public function __construct(GithubClientInterface $githubClient)
    {
        $this->githubClient = $githubClient;
        $this->versionParser = new VersionParser();
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestVersion()
    {
        $this->retrieveAvailableVersions();

        return $this->availableVersions[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestVersionOfMajor($majorVersion)
    {
        $this->retrieveAvailableVersions();

        $semverConstraint = '^'.$majorVersion;

        foreach ($this->availableVersions as $availableVersion) {
            if (Semver::satisfies($availableVersion, $semverConstraint)) {
                return $availableVersion;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function compareVersions($versionA, $versionB)
    {
        $versionA = $this->versionParser->normalize($versionA);
        $versionB = $this->versionParser->normalize($versionB);

        if (Comparator::lessThan($versionA, $versionB)) {
            return -1;
        }

        if (Comparator::greaterThan($versionA, $versionB)) {
            return 1;
        }

        return 0;
    }

    private function retrieveAvailableVersions()
    {
        if (null !== $this->availableVersions) {
            return;
        }

        foreach ($this->githubClient->getTags() as $tag) {
            $version = $tag['name'];

            try {
                $this->versionParser->normalize($version);

                if ('stable' === VersionParser::parseStability($version)) {
                    $this->availableVersions[] = $version;
                }
            } catch (\UnexpectedValueException $exception) {
                // not a valid version tag
            }
        }

        $this->availableVersions = Semver::rsort($this->availableVersions);
    }
}
