<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate\Attribute;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;

/**
 * Implements value for 'Access Identity' attribute.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.4.2
 */
final class AccessIdentityAttributeValue extends SvceAuthInfo
{
    final public const OID = '1.3.6.1.5.5.7.10.2';

    private function __construct(GeneralName $service, GeneralName $ident, ?string $authInfo)
    {
        parent::__construct(self::OID, $service, $ident, $authInfo);
    }

    public static function create(GeneralName $service, GeneralName $ident, ?string $_authInfo = null): self
    {
        return new self($service, $ident, $_authInfo);
    }

    public static function fromASN1(UnspecifiedType $el): static
    {
        $seq = $el->asSequence();
        $service = GeneralName::fromASN1($seq->at(0)->asTagged());
        $ident = GeneralName::fromASN1($seq->at(1)->asTagged());
        $auth_info = null;
        if ($seq->has(2, Element::TYPE_OCTET_STRING)) {
            $auth_info = $seq->at(2)
                ->asString()
                ->string();
        }
        return static::create($service, $ident, $auth_info);
    }
}
