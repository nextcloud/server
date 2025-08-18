<?php declare(strict_types=1);
/*
 * This file is part of sebastian/version.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann;

use function end;
use function explode;
use function fclose;
use function is_dir;
use function is_resource;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function substr_count;
use function trim;

final class Version
{
    private readonly string $version;

    public function __construct(string $release, string $path)
    {
        $this->version = $this->generate($release, $path);
    }

    public function asString(): string
    {
        return $this->version;
    }

    private function generate(string $release, string $path): string
    {
        if (substr_count($release, '.') + 1 === 3) {
            $version = $release;
        } else {
            $version = $release . '-dev';
        }

        $git = $this->getGitInformation($path);

        if (!$git) {
            return $version;
        }

        if (substr_count($release, '.') + 1 === 3) {
            return $git;
        }

        $git = explode('-', $git);

        return $release . '-' . end($git);
    }

    private function getGitInformation(string $path): bool|string
    {
        if (!is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            return false;
        }

        $process = proc_open(
            'git describe --tags',
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $path
        );

        if (!is_resource($process)) {
            return false;
        }

        $result = trim(stream_get_contents($pipes[1]));

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            return false;
        }

        return $result;
    }
}
