<?php

/**
 * Generic EC Key Parsing Helper functions
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\EC\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\EC\BaseCurves\Base as BaseCurve;
use phpseclib3\Crypt\EC\BaseCurves\Binary as BinaryCurve;
use phpseclib3\Crypt\EC\BaseCurves\Prime as PrimeCurve;
use phpseclib3\Crypt\EC\BaseCurves\TwistedEdwards as TwistedEdwardsCurve;
use phpseclib3\Exception\UnsupportedCurveException;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps;
use phpseclib3\Math\BigInteger;

/**
 * Generic EC Key Parsing Helper functions
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
trait Common
{
    /**
     * Curve OIDs
     *
     * @var array
     */
    private static $curveOIDs = [];

    /**
     * Child OIDs loaded
     *
     * @var bool
     */
    protected static $childOIDsLoaded = false;

    /**
     * Use Named Curves
     *
     * @var bool
     */
    private static $useNamedCurves = true;

    /**
     * Initialize static variables
     */
    private static function initialize_static_variables()
    {
        if (empty(self::$curveOIDs)) {
            // the sec* curves are from the standards for efficient cryptography group
            // sect* curves are curves over binary finite fields
            // secp* curves are curves over prime finite fields
            // sec*r* curves are regular curves; sec*k* curves are koblitz curves
            // brainpool*r* curves are regular prime finite field curves
            // brainpool*t* curves are twisted versions of the brainpool*r* curves
            self::$curveOIDs = [
                'prime192v1' => '1.2.840.10045.3.1.1', // J.5.1, example 1 (aka secp192r1)
                'prime192v2' => '1.2.840.10045.3.1.2', // J.5.1, example 2
                'prime192v3' => '1.2.840.10045.3.1.3', // J.5.1, example 3
                'prime239v1' => '1.2.840.10045.3.1.4', // J.5.2, example 1
                'prime239v2' => '1.2.840.10045.3.1.5', // J.5.2, example 2
                'prime239v3' => '1.2.840.10045.3.1.6', // J.5.2, example 3
                'prime256v1' => '1.2.840.10045.3.1.7', // J.5.3, example 1 (aka secp256r1)

                // https://tools.ietf.org/html/rfc5656#section-10
                'nistp256' => '1.2.840.10045.3.1.7', // aka secp256r1
                'nistp384' => '1.3.132.0.34', // aka secp384r1
                'nistp521' => '1.3.132.0.35', // aka secp521r1

                'nistk163' => '1.3.132.0.1', // aka sect163k1
                'nistp192' => '1.2.840.10045.3.1.1', // aka secp192r1
                'nistp224' => '1.3.132.0.33', // aka secp224r1
                'nistk233' => '1.3.132.0.26', // aka sect233k1
                'nistb233' => '1.3.132.0.27', // aka sect233r1
                'nistk283' => '1.3.132.0.16', // aka sect283k1
                'nistk409' => '1.3.132.0.36', // aka sect409k1
                'nistb409' => '1.3.132.0.37', // aka sect409r1
                'nistt571' => '1.3.132.0.38', // aka sect571k1

                // from https://tools.ietf.org/html/rfc5915
                'secp192r1' => '1.2.840.10045.3.1.1', // aka prime192v1
                'sect163k1' => '1.3.132.0.1',
                'sect163r2' => '1.3.132.0.15',
                'secp224r1' => '1.3.132.0.33',
                'sect233k1' => '1.3.132.0.26',
                'sect233r1' => '1.3.132.0.27',
                'secp256r1' => '1.2.840.10045.3.1.7', // aka prime256v1
                'sect283k1' => '1.3.132.0.16',
                'sect283r1' => '1.3.132.0.17',
                'secp384r1' => '1.3.132.0.34',
                'sect409k1' => '1.3.132.0.36',
                'sect409r1' => '1.3.132.0.37',
                'secp521r1' => '1.3.132.0.35',
                'sect571k1' => '1.3.132.0.38',
                'sect571r1' => '1.3.132.0.39',
                // from http://www.secg.org/SEC2-Ver-1.0.pdf
                'secp112r1' => '1.3.132.0.6',
                'secp112r2' => '1.3.132.0.7',
                'secp128r1' => '1.3.132.0.28',
                'secp128r2' => '1.3.132.0.29',
                'secp160k1' => '1.3.132.0.9',
                'secp160r1' => '1.3.132.0.8',
                'secp160r2' => '1.3.132.0.30',
                'secp192k1' => '1.3.132.0.31',
                'secp224k1' => '1.3.132.0.32',
                'secp256k1' => '1.3.132.0.10',

                'sect113r1' => '1.3.132.0.4',
                'sect113r2' => '1.3.132.0.5',
                'sect131r1' => '1.3.132.0.22',
                'sect131r2' => '1.3.132.0.23',
                'sect163r1' => '1.3.132.0.2',
                'sect193r1' => '1.3.132.0.24',
                'sect193r2' => '1.3.132.0.25',
                'sect239k1' => '1.3.132.0.3',

                // from http://citeseerx.ist.psu.edu/viewdoc/download?doi=10.1.1.202.2977&rep=rep1&type=pdf#page=36
                /*
                'c2pnb163v1' => '1.2.840.10045.3.0.1', // J.4.1, example 1
                'c2pnb163v2' => '1.2.840.10045.3.0.2', // J.4.1, example 2
                'c2pnb163v3' => '1.2.840.10045.3.0.3', // J.4.1, example 3
                'c2pnb172w1' => '1.2.840.10045.3.0.4', // J.4.2, example 1
                'c2tnb191v1' => '1.2.840.10045.3.0.5', // J.4.3, example 1
                'c2tnb191v2' => '1.2.840.10045.3.0.6', // J.4.3, example 2
                'c2tnb191v3' => '1.2.840.10045.3.0.7', // J.4.3, example 3
                'c2onb191v4' => '1.2.840.10045.3.0.8', // J.4.3, example 4
                'c2onb191v5' => '1.2.840.10045.3.0.9', // J.4.3, example 5
                'c2pnb208w1' => '1.2.840.10045.3.0.10', // J.4.4, example 1
                'c2tnb239v1' => '1.2.840.10045.3.0.11', // J.4.5, example 1
                'c2tnb239v2' => '1.2.840.10045.3.0.12', // J.4.5, example 2
                'c2tnb239v3' => '1.2.840.10045.3.0.13', // J.4.5, example 3
                'c2onb239v4' => '1.2.840.10045.3.0.14', // J.4.5, example 4
                'c2onb239v5' => '1.2.840.10045.3.0.15', // J.4.5, example 5
                'c2pnb272w1' => '1.2.840.10045.3.0.16', // J.4.6, example 1
                'c2pnb304w1' => '1.2.840.10045.3.0.17', // J.4.7, example 1
                'c2tnb359v1' => '1.2.840.10045.3.0.18', // J.4.8, example 1
                'c2pnb368w1' => '1.2.840.10045.3.0.19', // J.4.9, example 1
                'c2tnb431r1' => '1.2.840.10045.3.0.20', // J.4.10, example 1
                */

                // http://www.ecc-brainpool.org/download/Domain-parameters.pdf
                // https://tools.ietf.org/html/rfc5639
                'brainpoolP160r1' => '1.3.36.3.3.2.8.1.1.1',
                'brainpoolP160t1' => '1.3.36.3.3.2.8.1.1.2',
                'brainpoolP192r1' => '1.3.36.3.3.2.8.1.1.3',
                'brainpoolP192t1' => '1.3.36.3.3.2.8.1.1.4',
                'brainpoolP224r1' => '1.3.36.3.3.2.8.1.1.5',
                'brainpoolP224t1' => '1.3.36.3.3.2.8.1.1.6',
                'brainpoolP256r1' => '1.3.36.3.3.2.8.1.1.7',
                'brainpoolP256t1' => '1.3.36.3.3.2.8.1.1.8',
                'brainpoolP320r1' => '1.3.36.3.3.2.8.1.1.9',
                'brainpoolP320t1' => '1.3.36.3.3.2.8.1.1.10',
                'brainpoolP384r1' => '1.3.36.3.3.2.8.1.1.11',
                'brainpoolP384t1' => '1.3.36.3.3.2.8.1.1.12',
                'brainpoolP512r1' => '1.3.36.3.3.2.8.1.1.13',
                'brainpoolP512t1' => '1.3.36.3.3.2.8.1.1.14'
            ];
            ASN1::loadOIDs([
                'prime-field' => '1.2.840.10045.1.1',
                'characteristic-two-field' => '1.2.840.10045.1.2',
                'characteristic-two-basis' => '1.2.840.10045.1.2.3',
                // per http://www.secg.org/SEC1-Ver-1.0.pdf#page=84, gnBasis "not used here"
                'gnBasis' => '1.2.840.10045.1.2.3.1', // NULL
                'tpBasis' => '1.2.840.10045.1.2.3.2', // Trinomial
                'ppBasis' => '1.2.840.10045.1.2.3.3'  // Pentanomial
            ] + self::$curveOIDs);
        }
    }

    /**
     * Explicitly set the curve
     *
     * If the key contains an implicit curve phpseclib needs the curve
     * to be explicitly provided
     *
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     */
    public static function setImplicitCurve(BaseCurve $curve)
    {
        self::$implicitCurve = $curve;
    }

    /**
     * Returns an instance of \phpseclib3\Crypt\EC\BaseCurves\Base based
     * on the curve parameters
     *
     * @param array $params
     * @return \phpseclib3\Crypt\EC\BaseCurves\Base|false
     */
    protected static function loadCurveByParam(array $params)
    {
        if (count($params) > 1) {
            throw new \RuntimeException('No parameters are present');
        }
        if (isset($params['namedCurve'])) {
            $curve = '\phpseclib3\Crypt\EC\Curves\\' . $params['namedCurve'];
            if (!class_exists($curve)) {
                throw new UnsupportedCurveException('Named Curve of ' . $params['namedCurve'] . ' is not supported');
            }
            return new $curve();
        }
        if (isset($params['implicitCurve'])) {
            if (!isset(self::$implicitCurve)) {
                throw new \RuntimeException('Implicit curves can be provided by calling setImplicitCurve');
            }
            return self::$implicitCurve;
        }
        if (isset($params['specifiedCurve'])) {
            $data = $params['specifiedCurve'];
            switch ($data['fieldID']['fieldType']) {
                case 'prime-field':
                    $curve = new PrimeCurve();
                    $curve->setModulo($data['fieldID']['parameters']);
                    $curve->setCoefficients(
                        new BigInteger($data['curve']['a'], 256),
                        new BigInteger($data['curve']['b'], 256)
                    );
                    $point = self::extractPoint("\0" . $data['base'], $curve);
                    $curve->setBasePoint(...$point);
                    $curve->setOrder($data['order']);
                    return $curve;
                case 'characteristic-two-field':
                    $curve = new BinaryCurve();
                    $params = ASN1::decodeBER($data['fieldID']['parameters']);
                    $params = ASN1::asn1map($params[0], Maps\Characteristic_two::MAP);
                    $modulo = [(int) $params['m']->toString()];
                    switch ($params['basis']) {
                        case 'tpBasis':
                            $modulo[] = (int) $params['parameters']->toString();
                            break;
                        case 'ppBasis':
                            $temp = ASN1::decodeBER($params['parameters']);
                            $temp = ASN1::asn1map($temp[0], Maps\Pentanomial::MAP);
                            $modulo[] = (int) $temp['k3']->toString();
                            $modulo[] = (int) $temp['k2']->toString();
                            $modulo[] = (int) $temp['k1']->toString();
                    }
                    $modulo[] = 0;
                    $curve->setModulo(...$modulo);
                    $len = ceil($modulo[0] / 8);
                    $curve->setCoefficients(
                        Strings::bin2hex($data['curve']['a']),
                        Strings::bin2hex($data['curve']['b'])
                    );
                    $point = self::extractPoint("\0" . $data['base'], $curve);
                    $curve->setBasePoint(...$point);
                    $curve->setOrder($data['order']);
                    return $curve;
                default:
                    throw new UnsupportedCurveException('Field Type of ' . $data['fieldID']['fieldType'] . ' is not supported');
            }
        }
        throw new \RuntimeException('No valid parameters are present');
    }

    /**
     * Extract points from a string
     *
     * Supports both compressed and uncompressed points
     *
     * @param string $str
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @return object[]
     */
    public static function extractPoint($str, BaseCurve $curve)
    {
        if ($curve instanceof TwistedEdwardsCurve) {
            // first step of point deciding as discussed at the following URL's:
            // https://tools.ietf.org/html/rfc8032#section-5.1.3
            // https://tools.ietf.org/html/rfc8032#section-5.2.3
            $y = $str;
            $y = strrev($y);
            $sign = (bool) (ord($y[0]) & 0x80);
            $y[0] = $y[0] & chr(0x7F);
            $y = new BigInteger($y, 256);
            if ($y->compare($curve->getModulo()) >= 0) {
                throw new \RuntimeException('The Y coordinate should not be >= the modulo');
            }
            $point = $curve->recoverX($y, $sign);
            if (!$curve->verifyPoint($point)) {
                throw new \RuntimeException('Unable to verify that point exists on curve');
            }
            return $point;
        }

        // the first byte of a bit string represents the number of bits in the last byte that are to be ignored but,
        // currently, bit strings wanting a non-zero amount of bits trimmed are not supported
        if (($val = Strings::shift($str)) != "\0") {
            throw new \UnexpectedValueException('extractPoint expects the first byte to be null - not ' . Strings::bin2hex($val));
        }
        if ($str == "\0") {
            return [];
        }

        $keylen = strlen($str);
        $order = $curve->getLengthInBytes();
        // point compression is being used
        if ($keylen == $order + 1) {
            return $curve->derivePoint($str);
        }

        // point compression is not being used
        if ($keylen == 2 * $order + 1) {
            preg_match("#(.)(.{{$order}})(.{{$order}})#s", $str, $matches);
            list(, $w, $x, $y) = $matches;
            if ($w != "\4") {
                throw new \UnexpectedValueException('The first byte of an uncompressed point should be 04 - not ' . Strings::bin2hex($val));
            }
            $point = [
                $curve->convertInteger(new BigInteger($x, 256)),
                $curve->convertInteger(new BigInteger($y, 256))
            ];

            if (!$curve->verifyPoint($point)) {
                throw new \RuntimeException('Unable to verify that point exists on curve');
            }

            return $point;
        }

        throw new \UnexpectedValueException('The string representation of the points is not of an appropriate length');
    }

    /**
     * Encode Parameters
     *
     * @todo Maybe at some point this could be moved to __toString() for each of the curves?
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @param bool $returnArray optional
     * @param array $options optional
     * @return string|false
     */
    private static function encodeParameters(BaseCurve $curve, $returnArray = false, array $options = [])
    {
        $useNamedCurves = isset($options['namedCurve']) ? $options['namedCurve'] : self::$useNamedCurves;

        $reflect = new \ReflectionClass($curve);
        $name = $reflect->getShortName();
        if ($useNamedCurves) {
            if (isset(self::$curveOIDs[$name])) {
                if ($reflect->isFinal()) {
                    $reflect = $reflect->getParentClass();
                    $name = $reflect->getShortName();
                }
                return $returnArray ?
                    ['namedCurve' => $name] :
                    ASN1::encodeDER(['namedCurve' => $name], Maps\ECParameters::MAP);
            }
            foreach (new \DirectoryIterator(__DIR__ . '/../../Curves/') as $file) {
                if ($file->getExtension() != 'php') {
                    continue;
                }
                $testName = $file->getBasename('.php');
                $class = 'phpseclib3\Crypt\EC\Curves\\' . $testName;
                $reflect = new \ReflectionClass($class);
                if ($reflect->isFinal()) {
                    continue;
                }
                $candidate = new $class();
                switch ($name) {
                    case 'Prime':
                        if (!$candidate instanceof PrimeCurve) {
                            break;
                        }
                        if (!$candidate->getModulo()->equals($curve->getModulo())) {
                            break;
                        }
                        if ($candidate->getA()->toBytes() != $curve->getA()->toBytes()) {
                            break;
                        }
                        if ($candidate->getB()->toBytes() != $curve->getB()->toBytes()) {
                            break;
                        }

                        list($candidateX, $candidateY) = $candidate->getBasePoint();
                        list($curveX, $curveY) = $curve->getBasePoint();
                        if ($candidateX->toBytes() != $curveX->toBytes()) {
                            break;
                        }
                        if ($candidateY->toBytes() != $curveY->toBytes()) {
                            break;
                        }

                        return $returnArray ?
                            ['namedCurve' => $testName] :
                            ASN1::encodeDER(['namedCurve' => $testName], Maps\ECParameters::MAP);
                    case 'Binary':
                        if (!$candidate instanceof BinaryCurve) {
                            break;
                        }
                        if ($candidate->getModulo() != $curve->getModulo()) {
                            break;
                        }
                        if ($candidate->getA()->toBytes() != $curve->getA()->toBytes()) {
                            break;
                        }
                        if ($candidate->getB()->toBytes() != $curve->getB()->toBytes()) {
                            break;
                        }

                        list($candidateX, $candidateY) = $candidate->getBasePoint();
                        list($curveX, $curveY) = $curve->getBasePoint();
                        if ($candidateX->toBytes() != $curveX->toBytes()) {
                            break;
                        }
                        if ($candidateY->toBytes() != $curveY->toBytes()) {
                            break;
                        }

                        return $returnArray ?
                            ['namedCurve' => $testName] :
                            ASN1::encodeDER(['namedCurve' => $testName], Maps\ECParameters::MAP);
                }
            }
        }

        $order = $curve->getOrder();
        // we could try to calculate the order thusly:
        // https://crypto.stackexchange.com/a/27914/4520
        // https://en.wikipedia.org/wiki/Schoof%E2%80%93Elkies%E2%80%93Atkin_algorithm
        if (!$order) {
            throw new \RuntimeException('Specified Curves need the order to be specified');
        }
        $point = $curve->getBasePoint();
        $x = $point[0]->toBytes();
        $y = $point[1]->toBytes();

        if ($curve instanceof PrimeCurve) {
            /*
             * valid versions are:
             *
             * ecdpVer1:
             *   - neither the curve or the base point are generated verifiably randomly.
             * ecdpVer2:
             *   - curve and base point are generated verifiably at random and curve.seed is present
             * ecdpVer3:
             *   - base point is generated verifiably at random but curve is not. curve.seed is present
             */
            // other (optional) parameters can be calculated using the methods discused at
            // https://crypto.stackexchange.com/q/28947/4520
            $data = [
                'version' => 'ecdpVer1',
                'fieldID' => [
                    'fieldType' => 'prime-field',
                    'parameters' => $curve->getModulo()
                ],
                'curve' => [
                    'a' => $curve->getA()->toBytes(),
                    'b' => $curve->getB()->toBytes()
                ],
                'base' => "\4" . $x . $y,
                'order' => $order
            ];

            return $returnArray ?
                ['specifiedCurve' => $data] :
                ASN1::encodeDER(['specifiedCurve' => $data], Maps\ECParameters::MAP);
        }
        if ($curve instanceof BinaryCurve) {
            $modulo = $curve->getModulo();
            $basis = count($modulo);
            $m = array_shift($modulo);
            array_pop($modulo); // the last parameter should always be 0
            //rsort($modulo);
            switch ($basis) {
                case 3:
                    $basis = 'tpBasis';
                    $modulo = new BigInteger($modulo[0]);
                    break;
                case 5:
                    $basis = 'ppBasis';
                    // these should be in strictly ascending order (hence the commented out rsort above)
                    $modulo = [
                        'k1' => new BigInteger($modulo[2]),
                        'k2' => new BigInteger($modulo[1]),
                        'k3' => new BigInteger($modulo[0])
                    ];
                    $modulo = ASN1::encodeDER($modulo, Maps\Pentanomial::MAP);
                    $modulo = new ASN1\Element($modulo);
            }
            $params = ASN1::encodeDER([
                'm' => new BigInteger($m),
                'basis' => $basis,
                'parameters' => $modulo
            ], Maps\Characteristic_two::MAP);
            $params = new ASN1\Element($params);
            $a = ltrim($curve->getA()->toBytes(), "\0");
            if (!strlen($a)) {
                $a = "\0";
            }
            $b = ltrim($curve->getB()->toBytes(), "\0");
            if (!strlen($b)) {
                $b = "\0";
            }
            $data = [
                'version' => 'ecdpVer1',
                'fieldID' => [
                    'fieldType' => 'characteristic-two-field',
                    'parameters' => $params
                ],
                'curve' => [
                    'a' => $a,
                    'b' => $b
                ],
                'base' => "\4" . $x . $y,
                'order' => $order
            ];

            return $returnArray ?
                ['specifiedCurve' => $data] :
                ASN1::encodeDER(['specifiedCurve' => $data], Maps\ECParameters::MAP);
        }

        throw new UnsupportedCurveException('Curve cannot be serialized');
    }

    /**
     * Use Specified Curve
     *
     * A specified curve has all the coefficients, the base points, etc, explicitely included.
     * A specified curve is a more verbose way of representing a curve
     */
    public static function useSpecifiedCurve()
    {
        self::$useNamedCurves = false;
    }

    /**
     * Use Named Curve
     *
     * A named curve does not include any parameters. It is up to the EC parameters to
     * know what the coefficients, the base points, etc, are from the name of the curve.
     * A named curve is a more concise way of representing a curve
     */
    public static function useNamedCurve()
    {
        self::$useNamedCurves = true;
    }
}
