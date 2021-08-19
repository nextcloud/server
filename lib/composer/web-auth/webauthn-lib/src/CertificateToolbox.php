<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn;

use Assert\Assertion;
use function count;
use function in_array;
use InvalidArgumentException;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use function Safe\file_put_contents;
use function Safe\ksort;
use function Safe\mkdir;
use function Safe\rename;
use function Safe\sprintf;
use function Safe\tempnam;
use function Safe\unlink;
use Symfony\Component\Process\Process;

class CertificateToolbox
{
    /**
     * @deprecated "This method is deprecated since v3.3 and will be removed en v4.0. Please use Webauthn\CertificateChainChecker\CertificateChainChecker instead"
     *
     * @param string[] $authenticatorCertificates
     * @param string[] $trustedCertificates
     */
    public static function checkChain(array $authenticatorCertificates, array $trustedCertificates = []): void
    {
        if (0 === count($trustedCertificates)) {
            self::checkCertificatesValidity($authenticatorCertificates, true);

            return;
        }
        self::checkCertificatesValidity($authenticatorCertificates, false);

        $processArguments = ['-no-CAfile', '-no-CApath'];

        $caDirname = self::createTemporaryDirectory();
        $processArguments[] = '--CApath';
        $processArguments[] = $caDirname;

        foreach ($trustedCertificates as $certificate) {
            self::prepareCertificate($caDirname, $certificate, 'webauthn-trusted-', '.pem');
        }

        $rehashProcess = new Process(['openssl', 'rehash', $caDirname]);
        $rehashProcess->run();
        while ($rehashProcess->isRunning()) {
            //Just wait
        }
        if (!$rehashProcess->isSuccessful()) {
            throw new InvalidArgumentException('Invalid certificate or certificate chain');
        }

        $filenames = [];
        $leafCertificate = array_shift($authenticatorCertificates);
        $leafFilename = self::prepareCertificate(sys_get_temp_dir(), $leafCertificate, 'webauthn-leaf-', '.pem');
        $filenames[] = $leafFilename;

        foreach ($authenticatorCertificates as $certificate) {
            $untrustedFilename = self::prepareCertificate(sys_get_temp_dir(), $certificate, 'webauthn-untrusted-', '.pem');
            $processArguments[] = '-untrusted';
            $processArguments[] = $untrustedFilename;
            $filenames[] = $untrustedFilename;
        }

        $processArguments[] = $leafFilename;
        array_unshift($processArguments, 'openssl', 'verify');

        $process = new Process($processArguments);
        $process->run();
        while ($process->isRunning()) {
            //Just wait
        }

        foreach ($filenames as $filename) {
            try {
                unlink($filename);
            } catch (FilesystemException $e) {
                continue;
            }
        }
        self::deleteDirectory($caDirname);

        if (!$process->isSuccessful()) {
            throw new InvalidArgumentException('Invalid certificate or certificate chain');
        }
    }

    public static function fixPEMStructure(string $certificate, string $type = 'CERTIFICATE'): string
    {
        $pemCert = '-----BEGIN '.$type.'-----'.PHP_EOL;
        $pemCert .= chunk_split($certificate, 64, PHP_EOL);
        $pemCert .= '-----END '.$type.'-----'.PHP_EOL;

        return $pemCert;
    }

    public static function convertDERToPEM(string $certificate, string $type = 'CERTIFICATE'): string
    {
        $derCertificate = self::unusedBytesFix($certificate);

        return self::fixPEMStructure(base64_encode($derCertificate), $type);
    }

    /**
     * @param string[] $certificates
     *
     * @return string[]
     */
    public static function convertAllDERToPEM(array $certificates, string $type = 'CERTIFICATE'): array
    {
        $certs = [];
        foreach ($certificates as $publicKey) {
            $certs[] = self::convertDERToPEM($publicKey, $type);
        }

        return $certs;
    }

    private static function unusedBytesFix(string $certificate): string
    {
        $certificateHash = hash('sha256', $certificate);
        if (in_array($certificateHash, self::getCertificateHashes(), true)) {
            $certificate[mb_strlen($certificate, '8bit') - 257] = "\0";
        }

        return $certificate;
    }

    /**
     * @param string[] $certificates
     */
    private static function checkCertificatesValidity(array $certificates, bool $allowRootCertificate): void
    {
        foreach ($certificates as $certificate) {
            $parsed = openssl_x509_parse($certificate);
            Assertion::isArray($parsed, 'Unable to read the certificate');
            if (false === $allowRootCertificate) {
                self::checkRootCertificate($parsed);
            }

            Assertion::keyExists($parsed, 'validTo_time_t', 'The certificate has no validity period');
            Assertion::keyExists($parsed, 'validFrom_time_t', 'The certificate has no validity period');
            Assertion::lessOrEqualThan(time(), $parsed['validTo_time_t'], 'The certificate expired');
            Assertion::greaterOrEqualThan(time(), $parsed['validFrom_time_t'], 'The certificate is not usable yet');
        }
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private static function checkRootCertificate(array $parsed): void
    {
        Assertion::keyExists($parsed, 'subject', 'The certificate has no subject');
        Assertion::keyExists($parsed, 'issuer', 'The certificate has no issuer');
        $subject = $parsed['subject'];
        $issuer = $parsed['issuer'];
        ksort($subject);
        ksort($issuer);
        Assertion::notEq($subject, $issuer, 'Root certificates are not allowed');
    }

    /**
     * @return string[]
     */
    private static function getCertificateHashes(): array
    {
        return [
            '349bca1031f8c82c4ceca38b9cebf1a69df9fb3b94eed99eb3fb9aa3822d26e8',
            'dd574527df608e47ae45fbba75a2afdd5c20fd94a02419381813cd55a2a3398f',
            '1d8764f0f7cd1352df6150045c8f638e517270e8b5dda1c63ade9c2280240cae',
            'd0edc9a91a1677435a953390865d208c55b3183c6759c9b5a7ff494c322558eb',
            '6073c436dcd064a48127ddbf6032ac1a66fd59a0c24434f070d4e564c124c897',
            'ca993121846c464d666096d35f13bf44c1b05af205f9b4a1e00cf6cc10c5e511',
        ];
    }

    private static function createTemporaryDirectory(): string
    {
        $caDir = tempnam(sys_get_temp_dir(), 'webauthn-ca-');
        if (file_exists($caDir)) {
            unlink($caDir);
        }
        mkdir($caDir);
        if (!is_dir($caDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $caDir));
        }

        return $caDir;
    }

    private static function deleteDirectory(string $dirname): void
    {
        $rehashProcess = new Process(['rm', '-rf', $dirname]);
        $rehashProcess->run();
        while ($rehashProcess->isRunning()) {
            //Just wait
        }
    }

    private static function prepareCertificate(string $folder, string $certificate, string $prefix, string $suffix): string
    {
        $untrustedFilename = tempnam($folder, $prefix);
        rename($untrustedFilename, $untrustedFilename.$suffix);
        file_put_contents($untrustedFilename.$suffix, $certificate, FILE_APPEND);
        file_put_contents($untrustedFilename.$suffix, PHP_EOL, FILE_APPEND);

        return $untrustedFilename.$suffix;
    }
}
