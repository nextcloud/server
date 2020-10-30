<?php

namespace XdgBaseDir;

/**
 * Simple implementation of the XDG standard http://standards.freedesktop.org/basedir-spec/basedir-spec-latest.html
 *
 * Based on the python implementation https://github.com/takluyver/pyxdg/blob/master/xdg/BaseDirectory.py
 */
class Xdg
{
    const S_IFDIR = 040000; // directory
    const S_IRWXO = 00007;  // rwx other
    const S_IRWXG = 00056;  // rwx group
    const RUNTIME_DIR_FALLBACK = 'php-xdg-runtime-dir-fallback-';

    /**
     * @return string
     */
    public function getHomeDir()
    {
        return getenv('HOME') ?: (getenv('HOMEDRIVE') . DIRECTORY_SEPARATOR . getenv('HOMEPATH'));
    }

    /**
     * @return string
     */
    public function getHomeConfigDir()
    {
        if ($path = getenv('XDG_CONFIG_HOME')) {
            return $path;
        }

        $homeDir = $this->getHomeDir();

        $path = DIRECTORY_SEPARATOR === $homeDir ? $homeDir.'.config' : $homeDir . DIRECTORY_SEPARATOR . '.config';

        return $path;
    }

    /**
     * @return string
     */
    public function getHomeDataDir()
    {
        $path = getenv('XDG_DATA_HOME') ?: $this->getHomeDir() . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share';

        return $path;
    }

    /**
     * @return array
     */
    public function getConfigDirs()
    {
        $configDirs = getenv('XDG_CONFIG_DIRS') ? explode(':', getenv('XDG_CONFIG_DIRS')) : array('/etc/xdg');

        $paths = array_merge(array($this->getHomeConfigDir()), $configDirs);

        return $paths;
    }

    /**
     * @return array
     */
    public function getDataDirs()
    {
        $dataDirs = getenv('XDG_DATA_DIRS') ? explode(':', getenv('XDG_DATA_DIRS')) : array('/usr/local/share', '/usr/share');

        $paths = array_merge(array($this->getHomeDataDir()), $dataDirs);

        return $paths;
    }

    /**
     * @return string
     */
    public function getHomeCacheDir()
    {
        $path = getenv('XDG_CACHE_HOME') ?: $this->getHomeDir() . DIRECTORY_SEPARATOR . '.cache';

        return $path;

    }

    public function getRuntimeDir($strict=true)
    {
        if ($runtimeDir = getenv('XDG_RUNTIME_DIR')) {
            return $runtimeDir;
        }

        if ($strict) {
            throw new \RuntimeException('XDG_RUNTIME_DIR was not set');
        }

        $fallback = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::RUNTIME_DIR_FALLBACK . getenv('USER');

        $create = false;

        if (!is_dir($fallback)) {
            mkdir($fallback, 0700, true);
        }

        $st = lstat($fallback);

        # The fallback must be a directory
        if (!$st['mode'] & self::S_IFDIR) {
            rmdir($fallback);
            $create = true;
        } elseif ($st['uid'] != $this->getUid() ||
            $st['mode'] & (self::S_IRWXG | self::S_IRWXO)
        ) {
            rmdir($fallback);
            $create = true;
        }

        if ($create) {
            mkdir($fallback, 0700, true);
        }

        return $fallback;
    }

    private function getUid()
    {
        if (function_exists('posix_getuid')) {
            return posix_getuid();
        }

        return getmyuid();
    }
}
