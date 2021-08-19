<?php

namespace League\Flysystem\Adapter;

class Ftpd extends Ftp
{
    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        if ($path === '') {
            return ['type' => 'dir', 'path' => ''];
        }

        if (@ftp_chdir($this->getConnection(), $path) === true) {
            $this->setConnectionRoot();

            return ['type' => 'dir', 'path' => $path];
        }

        $object = ftp_raw($this->getConnection(), 'STAT ' . $this->escapePath($path));

        if ( ! $object || count($object) < 3) {
            return false;
        }

        if (substr($object[1], 0, 5) === "ftpd:") {
            return false;
        }

        return $this->normalizeObject($object[1], '');
    }

    /**
     * @inheritdoc
     */
    protected function listDirectoryContents($directory, $recursive = true)
    {
        $listing = ftp_rawlist($this->getConnection(), $this->escapePath($directory), $recursive);

        if ($listing === false || ( ! empty($listing) && substr($listing[0], 0, 5) === "ftpd:")) {
            return [];
        }

        return $this->normalizeListing($listing, $directory);
    }
}
