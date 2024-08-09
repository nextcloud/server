<?php

/**
 * XML Formatted EC Key Handler
 *
 * More info:
 *
 * https://www.w3.org/TR/xmldsig-core/#sec-ECKeyValue
 * http://en.wikipedia.org/wiki/XML_Signature
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
use phpseclib3\Crypt\EC\BaseCurves\Montgomery as MontgomeryCurve;
use phpseclib3\Crypt\EC\BaseCurves\Prime as PrimeCurve;
use phpseclib3\Crypt\EC\BaseCurves\TwistedEdwards as TwistedEdwardsCurve;
use phpseclib3\Exception\BadConfigurationException;
use phpseclib3\Exception\UnsupportedCurveException;
use phpseclib3\Math\BigInteger;

/**
 * XML Formatted EC Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class XML
{
    use Common;

    /**
     * Default namespace
     *
     * @var string
     */
    private static $namespace;

    /**
     * Flag for using RFC4050 syntax
     *
     * @var bool
     */
    private static $rfc4050 = false;

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        self::initialize_static_variables();

        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        if (!class_exists('DOMDocument')) {
            throw new BadConfigurationException('The dom extension is not setup correctly on this system');
        }

        $use_errors = libxml_use_internal_errors(true);

        $temp = self::isolateNamespace($key, 'http://www.w3.org/2009/xmldsig11#');
        if ($temp) {
            $key = $temp;
        }

        $temp = self::isolateNamespace($key, 'http://www.w3.org/2001/04/xmldsig-more#');
        if ($temp) {
            $key = $temp;
        }

        $dom = new \DOMDocument();
        if (substr($key, 0, 5) != '<?xml') {
            $key = '<xml>' . $key . '</xml>';
        }

        if (!$dom->loadXML($key)) {
            libxml_use_internal_errors($use_errors);
            throw new \UnexpectedValueException('Key does not appear to contain XML');
        }
        $xpath = new \DOMXPath($dom);
        libxml_use_internal_errors($use_errors);
        $curve = self::loadCurveByParam($xpath);

        $pubkey = self::query($xpath, 'publickey', 'Public Key is not present');

        $QA = self::query($xpath, 'ecdsakeyvalue')->length ?
            self::extractPointRFC4050($xpath, $curve) :
            self::extractPoint("\0" . $pubkey, $curve);

        libxml_use_internal_errors($use_errors);

        return compact('curve', 'QA');
    }

    /**
     * Case-insensitive xpath query
     *
     * @param \DOMXPath $xpath
     * @param string $name
     * @param string $error optional
     * @param bool $decode optional
     * @return \DOMNodeList
     */
    private static function query(\DOMXPath $xpath, $name, $error = null, $decode = true)
    {
        $query = '/';
        $names = explode('/', $name);
        foreach ($names as $name) {
            $query .= "/*[translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='$name']";
        }
        $result = $xpath->query($query);
        if (!isset($error)) {
            return $result;
        }

        if (!$result->length) {
            throw new \RuntimeException($error);
        }
        return $decode ? self::decodeValue($result->item(0)->textContent) : $result->item(0)->textContent;
    }

    /**
     * Finds the first element in the relevant namespace, strips the namespacing and returns the XML for that element.
     *
     * @param string $xml
     * @param string $ns
     */
    private static function isolateNamespace($xml, $ns)
    {
        $dom = new \DOMDocument();
        if (!$dom->loadXML($xml)) {
            return false;
        }
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//*[namespace::*[.='$ns'] and not(../namespace::*[.='$ns'])]");
        if (!$nodes->length) {
            return false;
        }
        $node = $nodes->item(0);
        $ns_name = $node->lookupPrefix($ns);
        if ($ns_name) {
            $node->removeAttributeNS($ns, $ns_name);
        }
        return $dom->saveXML($node);
    }

    /**
     * Decodes the value
     *
     * @param string $value
     */
    private static function decodeValue($value)
    {
        return Strings::base64_decode(str_replace(["\r", "\n", ' ', "\t"], '', $value));
    }

    /**
     * Extract points from an XML document
     *
     * @param \DOMXPath $xpath
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @return object[]
     */
    private static function extractPointRFC4050(\DOMXPath $xpath, BaseCurve $curve)
    {
        $x = self::query($xpath, 'publickey/x');
        $y = self::query($xpath, 'publickey/y');
        if (!$x->length || !$x->item(0)->hasAttribute('Value')) {
            throw new \RuntimeException('Public Key / X coordinate not found');
        }
        if (!$y->length || !$y->item(0)->hasAttribute('Value')) {
            throw new \RuntimeException('Public Key / Y coordinate not found');
        }
        $point = [
            $curve->convertInteger(new BigInteger($x->item(0)->getAttribute('Value'))),
            $curve->convertInteger(new BigInteger($y->item(0)->getAttribute('Value')))
        ];
        if (!$curve->verifyPoint($point)) {
            throw new \RuntimeException('Unable to verify that point exists on curve');
        }
        return $point;
    }

    /**
     * Returns an instance of \phpseclib3\Crypt\EC\BaseCurves\Base based
     * on the curve parameters
     *
     * @param \DomXPath $xpath
     * @return \phpseclib3\Crypt\EC\BaseCurves\Base|false
     */
    private static function loadCurveByParam(\DOMXPath $xpath)
    {
        $namedCurve = self::query($xpath, 'namedcurve');
        if ($namedCurve->length == 1) {
            $oid = $namedCurve->item(0)->getAttribute('URN');
            $oid = preg_replace('#[^\d.]#', '', $oid);
            $name = array_search($oid, self::$curveOIDs);
            if ($name === false) {
                throw new UnsupportedCurveException('Curve with OID of ' . $oid . ' is not supported');
            }

            $curve = '\phpseclib3\Crypt\EC\Curves\\' . $name;
            if (!class_exists($curve)) {
                throw new UnsupportedCurveException('Named Curve of ' . $name . ' is not supported');
            }
            return new $curve();
        }

        $params = self::query($xpath, 'explicitparams');
        if ($params->length) {
            return self::loadCurveByParamRFC4050($xpath);
        }

        $params = self::query($xpath, 'ecparameters');
        if (!$params->length) {
            throw new \RuntimeException('No parameters are present');
        }

        $fieldTypes = [
            'prime-field' => ['fieldid/prime/p'],
            'gnb' => ['fieldid/gnb/m'],
            'tnb' => ['fieldid/tnb/k'],
            'pnb' => ['fieldid/pnb/k1', 'fieldid/pnb/k2', 'fieldid/pnb/k3'],
            'unknown' => []
        ];

        foreach ($fieldTypes as $type => $queries) {
            foreach ($queries as $query) {
                $result = self::query($xpath, $query);
                if (!$result->length) {
                    continue 2;
                }
                $param = preg_replace('#.*/#', '', $query);
                $$param = self::decodeValue($result->item(0)->textContent);
            }
            break;
        }

        $a = self::query($xpath, 'curve/a', 'A coefficient is not present');
        $b = self::query($xpath, 'curve/b', 'B coefficient is not present');
        $base = self::query($xpath, 'base', 'Base point is not present');
        $order = self::query($xpath, 'order', 'Order is not present');

        switch ($type) {
            case 'prime-field':
                $curve = new PrimeCurve();
                $curve->setModulo(new BigInteger($p, 256));
                $curve->setCoefficients(
                    new BigInteger($a, 256),
                    new BigInteger($b, 256)
                );
                $point = self::extractPoint("\0" . $base, $curve);
                $curve->setBasePoint(...$point);
                $curve->setOrder(new BigInteger($order, 256));
                return $curve;
            case 'gnb':
            case 'tnb':
            case 'pnb':
            default:
                throw new UnsupportedCurveException('Field Type of ' . $type . ' is not supported');
        }
    }

    /**
     * Returns an instance of \phpseclib3\Crypt\EC\BaseCurves\Base based
     * on the curve parameters
     *
     * @param \DomXPath $xpath
     * @return \phpseclib3\Crypt\EC\BaseCurves\Base|false
     */
    private static function loadCurveByParamRFC4050(\DOMXPath $xpath)
    {
        $fieldTypes = [
            'prime-field' => ['primefieldparamstype/p'],
            'unknown' => []
        ];

        foreach ($fieldTypes as $type => $queries) {
            foreach ($queries as $query) {
                $result = self::query($xpath, $query);
                if (!$result->length) {
                    continue 2;
                }
                $param = preg_replace('#.*/#', '', $query);
                $$param = $result->item(0)->textContent;
            }
            break;
        }

        $a = self::query($xpath, 'curveparamstype/a', 'A coefficient is not present', false);
        $b = self::query($xpath, 'curveparamstype/b', 'B coefficient is not present', false);
        $x = self::query($xpath, 'basepointparams/basepoint/ecpointtype/x', 'Base Point X is not present', false);
        $y = self::query($xpath, 'basepointparams/basepoint/ecpointtype/y', 'Base Point Y is not present', false);
        $order = self::query($xpath, 'order', 'Order is not present', false);

        switch ($type) {
            case 'prime-field':
                $curve = new PrimeCurve();

                $p = str_replace(["\r", "\n", ' ', "\t"], '', $p);
                $curve->setModulo(new BigInteger($p));

                $a = str_replace(["\r", "\n", ' ', "\t"], '', $a);
                $b = str_replace(["\r", "\n", ' ', "\t"], '', $b);
                $curve->setCoefficients(
                    new BigInteger($a),
                    new BigInteger($b)
                );

                $x = str_replace(["\r", "\n", ' ', "\t"], '', $x);
                $y = str_replace(["\r", "\n", ' ', "\t"], '', $y);
                $curve->setBasePoint(
                    new BigInteger($x),
                    new BigInteger($y)
                );

                $order = str_replace(["\r", "\n", ' ', "\t"], '', $order);
                $curve->setOrder(new BigInteger($order));
                return $curve;
            default:
                throw new UnsupportedCurveException('Field Type of ' . $type . ' is not supported');
        }
    }

    /**
     * Sets the namespace. dsig11 is the most common one.
     *
     * Set to null to unset. Used only for creating public keys.
     *
     * @param string $namespace
     */
    public static function setNamespace($namespace)
    {
        self::$namespace = $namespace;
    }

    /**
     * Uses the XML syntax specified in https://tools.ietf.org/html/rfc4050
     */
    public static function enableRFC4050Syntax()
    {
        self::$rfc4050 = true;
    }

    /**
     * Uses the XML syntax specified in https://www.w3.org/TR/xmldsig-core/#sec-ECParameters
     */
    public static function disableRFC4050Syntax()
    {
        self::$rfc4050 = false;
    }

    /**
     * Convert a public key to the appropriate format
     *
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @param \phpseclib3\Math\Common\FiniteField\Integer[] $publicKey
     * @param array $options optional
     * @return string
     */
    public static function savePublicKey(BaseCurve $curve, array $publicKey, array $options = [])
    {
        self::initialize_static_variables();

        if ($curve instanceof TwistedEdwardsCurve || $curve instanceof MontgomeryCurve) {
            throw new UnsupportedCurveException('TwistedEdwards and Montgomery Curves are not supported');
        }

        if (empty(static::$namespace)) {
            $pre = $post = '';
        } else {
            $pre = static::$namespace . ':';
            $post = ':' . static::$namespace;
        }

        if (self::$rfc4050) {
            return '<' . $pre . 'ECDSAKeyValue xmlns' . $post . '="http://www.w3.org/2001/04/xmldsig-more#">' . "\r\n" .
                   self::encodeXMLParameters($curve, $pre, $options) . "\r\n" .
                   '<' . $pre . 'PublicKey>' . "\r\n" .
                   '<' . $pre . 'X Value="' . $publicKey[0] . '" />' . "\r\n" .
                   '<' . $pre . 'Y Value="' . $publicKey[1] . '" />' . "\r\n" .
                   '</' . $pre . 'PublicKey>' . "\r\n" .
                   '</' . $pre . 'ECDSAKeyValue>';
        }

        $publicKey = "\4" . $publicKey[0]->toBytes() . $publicKey[1]->toBytes();

        return '<' . $pre . 'ECDSAKeyValue xmlns' . $post . '="http://www.w3.org/2009/xmldsig11#">' . "\r\n" .
               self::encodeXMLParameters($curve, $pre, $options) . "\r\n" .
               '<' . $pre . 'PublicKey>' . Strings::base64_encode($publicKey) . '</' . $pre . 'PublicKey>' . "\r\n" .
               '</' . $pre . 'ECDSAKeyValue>';
    }

    /**
     * Encode Parameters
     *
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @param string $pre
     * @param array $options optional
     * @return string|false
     */
    private static function encodeXMLParameters(BaseCurve $curve, $pre, array $options = [])
    {
        $result = self::encodeParameters($curve, true, $options);

        if (isset($result['namedCurve'])) {
            $namedCurve = '<' . $pre . 'NamedCurve URI="urn:oid:' . self::$curveOIDs[$result['namedCurve']] . '" />';
            return self::$rfc4050 ?
                '<DomainParameters>' . str_replace('URI', 'URN', $namedCurve) . '</DomainParameters>' :
                $namedCurve;
        }

        if (self::$rfc4050) {
            $xml = '<' . $pre . 'ExplicitParams>' . "\r\n" .
                  '<' . $pre . 'FieldParams>' . "\r\n";
            $temp = $result['specifiedCurve'];
            switch ($temp['fieldID']['fieldType']) {
                case 'prime-field':
                    $xml .= '<' . $pre . 'PrimeFieldParamsType>' . "\r\n" .
                           '<' . $pre . 'P>' . $temp['fieldID']['parameters'] . '</' . $pre . 'P>' . "\r\n" .
                           '</' . $pre . 'PrimeFieldParamsType>' . "\r\n";
                    $a = $curve->getA();
                    $b = $curve->getB();
                    list($x, $y) = $curve->getBasePoint();
                    break;
                default:
                    throw new UnsupportedCurveException('Field Type of ' . $temp['fieldID']['fieldType'] . ' is not supported');
            }
            $xml .= '</' . $pre . 'FieldParams>' . "\r\n" .
                   '<' . $pre . 'CurveParamsType>' . "\r\n" .
                   '<' . $pre . 'A>' . $a . '</' . $pre . 'A>' . "\r\n" .
                   '<' . $pre . 'B>' . $b . '</' . $pre . 'B>' . "\r\n" .
                   '</' . $pre . 'CurveParamsType>' . "\r\n" .
                   '<' . $pre . 'BasePointParams>' . "\r\n" .
                   '<' . $pre . 'BasePoint>' . "\r\n" .
                   '<' . $pre . 'ECPointType>' . "\r\n" .
                   '<' . $pre . 'X>' . $x . '</' . $pre . 'X>' . "\r\n" .
                   '<' . $pre . 'Y>' . $y . '</' . $pre . 'Y>' . "\r\n" .
                   '</' . $pre . 'ECPointType>' . "\r\n" .
                   '</' . $pre . 'BasePoint>' . "\r\n" .
                   '<' . $pre . 'Order>' . $curve->getOrder() . '</' . $pre . 'Order>' . "\r\n" .
                   '</' . $pre . 'BasePointParams>' . "\r\n" .
                   '</' . $pre . 'ExplicitParams>' . "\r\n";

            return $xml;
        }

        if (isset($result['specifiedCurve'])) {
            $xml = '<' . $pre . 'ECParameters>' . "\r\n" .
                   '<' . $pre . 'FieldID>' . "\r\n";
            $temp = $result['specifiedCurve'];
            switch ($temp['fieldID']['fieldType']) {
                case 'prime-field':
                    $xml .= '<' . $pre . 'Prime>' . "\r\n" .
                           '<' . $pre . 'P>' . Strings::base64_encode($temp['fieldID']['parameters']->toBytes()) . '</' . $pre . 'P>' . "\r\n" .
                           '</' . $pre . 'Prime>' . "\r\n" ;
                    break;
                default:
                    throw new UnsupportedCurveException('Field Type of ' . $temp['fieldID']['fieldType'] . ' is not supported');
            }
            $xml .= '</' . $pre . 'FieldID>' . "\r\n" .
                   '<' . $pre . 'Curve>' . "\r\n" .
                   '<' . $pre . 'A>' . Strings::base64_encode($temp['curve']['a']) . '</' . $pre . 'A>' . "\r\n" .
                   '<' . $pre . 'B>' . Strings::base64_encode($temp['curve']['b']) . '</' . $pre . 'B>' . "\r\n" .
                   '</' . $pre . 'Curve>' . "\r\n" .
                   '<' . $pre . 'Base>' . Strings::base64_encode($temp['base']) . '</' . $pre . 'Base>' . "\r\n" .
                   '<' . $pre . 'Order>' . Strings::base64_encode($temp['order']) . '</' . $pre . 'Order>' . "\r\n" .
                   '</' . $pre . 'ECParameters>';
            return $xml;
        }
    }
}
