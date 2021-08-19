<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process\Pipes;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * WindowsPipes implementation uses temporary files as handles.
 *
 * @see https://bugs.php.net/51800
 * @see https://bugs.php.net/65650
 *
 * @author Romain Neutron <imprec@gmail.com>
 *
 * @internal
 */
class WindowsPipes extends AbstractPipes
{
    private $files = [];
    private $fileHandles = [];
    private $lockHandles = [];
    private $readBytes = [
        Process::STDOUT => 0,
        Process::STDERR => 0,
    ];
    private $haveReadSupport;

    public function __construct($input, bool $haveReadSupport)
    {
        $this->haveReadSupport = $haveReadSupport;

        if ($this->haveReadSupport) {
            // Fix for PHP bug #51800: reading from STDOUT pipe hangs forever on Windows if the output is too big.
            // Workaround for this problem is to use temporary files instead of pipes on Windows platform.
            //
            // @see https://bugs.php.net/51800
            $pipes = [
                Process::STDOUT => Process::OUT,
                Process::STDERR => Process::ERR,
            ];
            $tmpDir = sys_get_temp_dir();
            $lastError = 'unknown reason';
            set_error_handler(function ($type, $msg) use (&$lastError) { $lastError = $msg; });
            for ($i = 0;; ++$i) {
                foreach ($pipes as $pipe => $name) {
                    $file = sprintf('%s\\sf_proc_%02X.%s', $tmpDir, $i, $name);

                    if (!$h = fopen($file.'.lock', 'w')) {
                        if (file_exists($file.'.lock')) {
                            continue 2;
                        }
                        restore_error_handler();
                        throw new RuntimeException('A temporary file could not be opened to write the process output: '.$lastError);
                    }
                    if (!flock($h, \LOCK_EX | \LOCK_NB)) {
                        continue 2;
                    }
                    if (isset($this->lockHandles[$pipe])) {
                        flock($this->lockHandles[$pipe], \LOCK_UN);
                        fclose($this->lockHandles[$pipe]);
                    }
                    $this->lockHandles[$pipe] = $h;

                    if (!fclose(fopen($file, 'w')) || !$h = fopen($file, 'r')) {
                        flock($this->lockHandles[$pipe], \LOCK_UN);
                        fclose($this->lockHandles[$pipe]);
                        unset($this->lockHandles[$pipe]);
                        continue 2;
                    }
                    $this->fileHandles[$pipe] = $h;
                    $this->files[$pipe] = $file;
                }
                break;
            }
            restore_error_handler();
        }

        parent::__construct($input);
    }

    public function __sleep()
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescriptors(): array
    {
        if (!$this->haveReadSupport) {
            $nullstream = fopen('NUL', 'c');

            return [
                ['pipe', 'r'],
                $nullstream,
                $nullstream,
            ];
        }

        // We're not using pipe on Windows platform as it hangs (https://bugs.php.net/51800)
        // We're not using file handles as it can produce corrupted output https://bugs.php.net/65650
        // So we redirect output within the commandline and pass the nul device to the process
        return [
            ['pipe', 'r'],
            ['file', 'NUL', 'w'],
            ['file', 'NUL', 'w'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * {@inheritdoc}
     */
    public function readAndWrite(bool $blocking, bool $close = false): array
    {
        $this->unblock();
        $w = $this->write();
        $read = $r = $e = [];

        if ($blocking) {
            if ($w) {
                @stream_select($r, $w, $e, 0, Process::TIMEOUT_PRECISION * 1E6);
            } elseif ($this->fileHandles) {
                usleep(Process::TIMEOUT_PRECISION * 1E6);
            }
        }
        foreach ($this->fileHandles as $type => $fileHandle) {
            $data = stream_get_contents($fileHandle, -1, $this->readBytes[$type]);

            if (isset($data[0])) {
                $this->readBytes[$type] += \strlen($data);
                $read[$type] = $data;
            }
            if ($close) {
                ftruncate($fileHandle, 0);
                fclose($fileHandle);
                flock($this->lockHandles[$type], \LOCK_UN);
                fclose($this->lockHandles[$type]);
                unset($this->fileHandles[$type], $this->lockHandles[$type]);
            }
        }

        return $read;
    }

    /**
     * {@inheritdoc}
     */
    public function haveReadSupport(): bool
    {
        return $this->haveReadSupport;
    }

    /**
     * {@inheritdoc}
     */
    public function areOpen(): bool
    {
        return $this->pipes && $this->fileHandles;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();
        foreach ($this->fileHandles as $type => $handle) {
            ftruncate($handle, 0);
            fclose($handle);
            flock($this->lockHandles[$type], \LOCK_UN);
            fclose($this->lockHandles[$type]);
        }
        $this->fileHandles = $this->lockHandles = [];
    }
}
