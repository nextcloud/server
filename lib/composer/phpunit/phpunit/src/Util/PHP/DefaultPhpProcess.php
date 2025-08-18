<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Util\PHP;

use function array_merge;
use function fclose;
use function file_put_contents;
use function fwrite;
use function is_array;
use function is_resource;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Exception;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
class DefaultPhpProcess extends AbstractPhpProcess
{
    private ?string $tempFile = null;

    /**
     * Runs a single job (PHP code) using a separate PHP process.
     *
     * @throws Exception
     * @throws PhpProcessException
     *
     * @psalm-return array{stdout: string, stderr: string}
     */
    public function runJob(string $job, array $settings = []): array
    {
        if ($this->stdin) {
            if (!($this->tempFile = tempnam(sys_get_temp_dir(), 'phpunit_')) ||
                file_put_contents($this->tempFile, $job) === false) {
                throw new PhpProcessException(
                    'Unable to write temporary file',
                );
            }

            $job = $this->stdin;
        }

        return $this->runProcess($job, $settings);
    }

    /**
     * Handles creating the child process and returning the STDOUT and STDERR.
     *
     * @throws Exception
     * @throws PhpProcessException
     *
     * @psalm-return array{stdout: string, stderr: string}
     */
    protected function runProcess(string $job, array $settings): array
    {
        $env = null;

        if ($this->env) {
            $env = $_SERVER ?? [];
            unset($env['argv'], $env['argc']);
            $env = array_merge($env, $this->env);

            foreach ($env as $envKey => $envVar) {
                if (is_array($envVar)) {
                    unset($env[$envKey]);
                }
            }
        }

        $pipeSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        if ($this->stderrRedirection) {
            $pipeSpec[2] = ['redirect', 1];
        }

        $process = proc_open(
            $this->getCommand($settings, $this->tempFile),
            $pipeSpec,
            $pipes,
            null,
            $env,
        );

        if (!is_resource($process)) {
            throw new PhpProcessException(
                'Unable to spawn worker process',
            );
        }

        if ($job) {
            $this->process($pipes[0], $job);
        }

        fclose($pipes[0]);

        $stderr = $stdout = '';

        if (isset($pipes[1])) {
            $stdout = stream_get_contents($pipes[1]);

            fclose($pipes[1]);
        }

        if (isset($pipes[2])) {
            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[2]);
        }

        proc_close($process);

        $this->cleanup();

        return ['stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * @param resource $pipe
     */
    protected function process($pipe, string $job): void
    {
        fwrite($pipe, $job);
    }

    protected function cleanup(): void
    {
        if ($this->tempFile) {
            unlink($this->tempFile);
        }
    }
}
