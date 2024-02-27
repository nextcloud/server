<?php

/** @generate-class-entries */

namespace FTP {

    /**
     * @strict-properties
     * @not-serializable
     */
    final class Connection
    {
    }

}

namespace {

    function ftp_connect(string $hostname, int $port = 21, int $timeout = 90): FTP\Connection|resource|false {}

    #ifdef HAVE_FTP_SSL
    function ftp_ssl_connect(string $hostname, int $port = 21, int $timeout = 90): FTP\Connection|resource|false {}
    #endif

    function ftp_login(FTP\Connection|resource $ftp, string $username, string $password): bool {}
    function ftp_pwd(FTP\Connection|resource $ftp): string|false {}
    function ftp_cdup(FTP\Connection|resource $ftp): bool {}
    function ftp_chdir(FTP\Connection|resource $ftp, string $directory): bool {}
    function ftp_exec(FTP\Connection|resource $ftp, string $command): bool {}
    function ftp_raw(FTP\Connection|resource $ftp, string $command): ?array {}
    function ftp_mkdir(FTP\Connection|resource $ftp, string $directory): string|false {}
    function ftp_rmdir(FTP\Connection|resource $ftp, string $directory): bool {}
    function ftp_chmod(FTP\Connection|resource $ftp, int $permissions, string $filename): int|false {}

    /** @param string $response */
    function ftp_alloc(FTP\Connection|resource $ftp, int $size, &$response = null): bool {}
    function ftp_nlist(FTP\Connection|resource $ftp, string $directory): array|false {}
    function ftp_rawlist(FTP\Connection|resource $ftp, string $directory, bool $recursive = false): array|false {}
    function ftp_mlsd(FTP\Connection|resource $ftp, string $directory): array|false {}
    function ftp_systype(FTP\Connection|resource $ftp): string|false {}

    /** @param resource $stream */
    function ftp_fget(FTP\Connection|resource $ftp, $stream, string $remote_filename, int $mode = FTP_BINARY, int $offset = 0): bool {}

    /** @param resource $stream */
    function ftp_nb_fget(FTP\Connection|resource $ftp, $stream, string $remote_filename, int $mode = FTP_BINARY, int $offset = 0): int {}
    function ftp_pasv(FTP\Connection|resource $ftp, bool $enable): bool {}
    function ftp_get(FTP\Connection|resource $ftp, string $local_filename, string $remote_filename, int $mode = FTP_BINARY, int $offset = 0): bool {}
    function ftp_nb_get(FTP\Connection|resource $ftp, string $local_filename, string $remote_filename, int $mode = FTP_BINARY, int $offset = 0): int {}
    function ftp_nb_continue(FTP\Connection|resource $ftp): int {}

    /** @param resource $stream */
    function ftp_fput(FTP\Connection|resource $ftp, string $remote_filename, $stream, int $mode = FTP_BINARY, int $offset = 0): bool {}

    /** @param resource $stream */
    function ftp_nb_fput(FTP\Connection|resource $ftp, string $remote_filename, $stream, int $mode = FTP_BINARY, int $offset = 0): int {}
    function ftp_put(FTP\Connection|resource $ftp, string $remote_filename, string $local_filename, int $mode = FTP_BINARY, int $offset = 0): bool {}
    function ftp_append(FTP\Connection|resource $ftp, string $remote_filename, string $local_filename, int $mode = FTP_BINARY): bool {}
    function ftp_nb_put(FTP\Connection|resource $ftp, string $remote_filename, string $local_filename, int $mode = FTP_BINARY, int $offset = 0): int|false {}
    function ftp_size(FTP\Connection|resource $ftp, string $filename): int {}
    function ftp_mdtm(FTP\Connection|resource $ftp, string $filename): int {}
    function ftp_rename(FTP\Connection|resource $ftp, string $from, string $to): bool {}
    function ftp_delete(FTP\Connection|resource $ftp, string $filename): bool {}
    function ftp_site(FTP\Connection|resource $ftp, string $command): bool {}
    function ftp_close(FTP\Connection|resource $ftp): bool {}

    /** @alias ftp_close */
    function ftp_quit(FTP\Connection|resource $ftp): bool {}

    /** @param int|bool $value */
    function ftp_set_option(FTP\Connection|resource $ftp, int $option, $value): bool {}
    function ftp_get_option(FTP\Connection|resource $ftp, int $option): int|bool {}

}
