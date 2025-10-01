<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\RSA;

use Cose\Algorithm\Signature\Signature;
use Cose\BigInteger;
use Cose\Hash;
use Cose\Key\Key;
use Cose\Key\RsaKey;
use InvalidArgumentException;
use RuntimeException;
use function ceil;
use function chr;
use function hash_equals;
use function mb_strlen;
use function mb_substr;
use function ord;
use function pack;
use function random_bytes;
use function str_pad;
use function str_repeat;
use const STR_PAD_LEFT;

/**
 * @internal
 */
abstract class PSSRSA implements Signature
{
    public function sign(string $data, Key $key): string
    {
        $key = $this->handleKey($key);
        $modulusLength = mb_strlen($key->n(), '8bit');

        $em = $this->encodeEMSAPSS($data, 8 * $modulusLength - 1, $this->getHashAlgorithm());
        $message = BigInteger::createFromBinaryString($em);
        $signature = $this->exponentiate($key, $message);

        return $this->convertIntegerToOctetString($signature, $modulusLength);
    }

    public function verify(string $data, Key $key, string $signature): bool
    {
        $key = $this->handleKey($key);
        $modulusLength = mb_strlen($key->n(), '8bit');
        if (mb_strlen($signature, '8bit') !== $modulusLength) {
            throw new InvalidArgumentException('Invalid modulus length');
        }
        $s2 = BigInteger::createFromBinaryString($signature);
        $m2 = $this->exponentiate($key, $s2);
        $em = $this->convertIntegerToOctetString($m2, $modulusLength);
        $modBits = 8 * $modulusLength;

        return $this->verifyEMSAPSS($data, $em, $modBits - 1, $this->getHashAlgorithm());
    }

    /**
     * Exponentiate with or without Chinese Remainder Theorem. Operation with primes 'p' and 'q' is appox. 2x faster.
     */
    public function exponentiate(RsaKey $key, BigInteger $c): BigInteger
    {
        if ($c->compare(BigInteger::createFromDecimal(0)) < 0 || $c->compare(
            BigInteger::createFromBinaryString($key->n())
        ) > 0) {
            throw new RuntimeException();
        }
        if ($key->isPublic() || ! $key->hasPrimes() || ! $key->hasExponents() || ! $key->hasCoefficient()) {
            return $c->modPow(
                BigInteger::createFromBinaryString($key->e()),
                BigInteger::createFromBinaryString($key->n())
            );
        }

        [$pS, $qS] = $key->primes();
        [$dPS, $dQS] = $key->exponents();
        $qInv = BigInteger::createFromBinaryString($key->QInv());
        $p = BigInteger::createFromBinaryString($pS);
        $q = BigInteger::createFromBinaryString($qS);
        $dP = BigInteger::createFromBinaryString($dPS);
        $dQ = BigInteger::createFromBinaryString($dQS);

        $m1 = $c->modPow($dP, $p);
        $m2 = $c->modPow($dQ, $q);
        $h = $qInv->multiply($m1->subtract($m2)->add($p))
            ->mod($p)
        ;

        return $m2->add($h->multiply($q));
    }

    abstract protected function getHashAlgorithm(): Hash;

    private function handleKey(Key $key): RsaKey
    {
        return RsaKey::create($key->getData());
    }

    private function convertIntegerToOctetString(BigInteger $x, int $xLen): string
    {
        $xB = $x->toBytes();
        if (mb_strlen($xB, '8bit') > $xLen) {
            throw new RuntimeException('Unable to convert the integer');
        }

        return str_pad($xB, $xLen, chr(0), STR_PAD_LEFT);
    }

    /**
     * MGF1.
     */
    private function getMGF1(string $mgfSeed, int $maskLen, Hash $mgfHash): string
    {
        $t = '';
        $count = ceil($maskLen / $mgfHash->getLength());
        for ($i = 0; $i < $count; ++$i) {
            $c = pack('N', $i);
            $t .= $mgfHash->hash($mgfSeed . $c);
        }

        return mb_substr($t, 0, $maskLen, '8bit');
    }

    /**
     * EMSA-PSS-ENCODE.
     */
    private function encodeEMSAPSS(string $message, int $modulusLength, Hash $hash): string
    {
        $emLen = ($modulusLength + 1) >> 3;
        $sLen = $hash->getLength();
        $mHash = $hash->hash($message);
        if ($emLen <= $hash->getLength() + $sLen + 2) {
            throw new RuntimeException();
        }
        $salt = random_bytes($sLen);
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h = $hash->hash($m2);
        $ps = str_repeat(chr(0), $emLen - $sLen - $hash->getLength() - 2);
        $db = $ps . chr(1) . $salt;
        $dbMask = $this->getMGF1($h, $emLen - $hash->getLength() - 1, $hash);
        $maskedDB = $db ^ $dbMask;
        $maskedDB[0] = ~chr(0xFF << ($modulusLength & 7)) & $maskedDB[0];

        return $maskedDB . $h . chr(0xBC);
    }

    /**
     * EMSA-PSS-VERIFY.
     */
    private function verifyEMSAPSS(string $m, string $em, int $emBits, Hash $hash): bool
    {
        $emLen = ($emBits + 1) >> 3;
        $sLen = $hash->getLength();
        $mHash = $hash->hash($m);
        if ($emLen < $hash->getLength() + $sLen + 2) {
            throw new InvalidArgumentException();
        }
        if ($em[mb_strlen($em, '8bit') - 1] !== chr(0xBC)) {
            throw new InvalidArgumentException();
        }
        $maskedDB = mb_substr($em, 0, -$hash->getLength() - 1, '8bit');
        $h = mb_substr($em, -$hash->getLength() - 1, $hash->getLength(), '8bit');
        $temp = chr(0xFF << ($emBits & 7));
        if ((~$maskedDB[0] & $temp) !== $temp) {
            throw new InvalidArgumentException();
        }
        $dbMask = $this->getMGF1($h, $emLen - $hash->getLength() - 1, $hash/*MGF*/);
        $db = $maskedDB ^ $dbMask;
        $db[0] = ~chr(0xFF << ($emBits & 7)) & $db[0];
        $temp = $emLen - $hash->getLength() - $sLen - 2;
        if (mb_strpos($db, str_repeat(chr(0), $temp), 0, '8bit') !== 0) {
            throw new InvalidArgumentException();
        }
        if (ord($db[$temp]) !== 1) {
            throw new InvalidArgumentException();
        }
        $salt = mb_substr($db, $temp + 1, null, '8bit'); // should be $sLen long
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h2 = $hash->hash($m2);

        return hash_equals($h, $h2);
    }
}
