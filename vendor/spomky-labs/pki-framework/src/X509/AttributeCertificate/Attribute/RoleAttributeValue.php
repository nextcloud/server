<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate\Attribute;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\AttributeType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\MatchingRule\BinaryMatch;
use SpomkyLabs\Pki\X501\MatchingRule\MatchingRule;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;
use SpomkyLabs\Pki\X509\GeneralName\UniformResourceIdentifier;

/**
 * Implements value for 'Role' attribute.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.4.5
 */
final class RoleAttributeValue extends AttributeValue
{
    /**
     * @param GeneralName $roleName Role name
     * @param null|GeneralNames $roleAuthority Issuing authority
     */
    private function __construct(
        private readonly GeneralName $roleName,
        private readonly ?GeneralNames $roleAuthority
    ) {
        parent::__construct(AttributeType::OID_ROLE);
    }

    public static function create(GeneralName $roleName, ?GeneralNames $roleAuthority = null): self
    {
        return new self($roleName, $roleAuthority);
    }

    /**
     * Initialize from a role string.
     *
     * @param string $role_name Role name in URI format
     * @param null|GeneralNames $authority Issuing authority
     */
    public static function fromString(string $role_name, ?GeneralNames $authority = null): self
    {
        return self::create(UniformResourceIdentifier::create($role_name), $authority);
    }

    /**
     * @return self
     */
    public static function fromASN1(UnspecifiedType $el): AttributeValue
    {
        $seq = $el->asSequence();
        $authority = null;
        if ($seq->hasTagged(0)) {
            $authority = GeneralNames::fromASN1(
                $seq->getTagged(0)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        $name = GeneralName::fromASN1($seq->getTagged(1)->asExplicit()->asTagged());
        return self::create($name, $authority);
    }

    /**
     * Check whether issuing authority is present.
     */
    public function hasRoleAuthority(): bool
    {
        return isset($this->roleAuthority);
    }

    /**
     * Get issuing authority.
     */
    public function roleAuthority(): GeneralNames
    {
        if (! $this->hasRoleAuthority()) {
            throw new LogicException('roleAuthority not set.');
        }
        return $this->roleAuthority;
    }

    /**
     * Get role name.
     */
    public function roleName(): GeneralName
    {
        return $this->roleName;
    }

    public function toASN1(): Element
    {
        $elements = [];
        if (isset($this->roleAuthority)) {
            $elements[] = ImplicitlyTaggedType::create(0, $this->roleAuthority->toASN1());
        }
        $elements[] = ExplicitlyTaggedType::create(1, $this->roleName->toASN1());
        return Sequence::create(...$elements);
    }

    public function stringValue(): string
    {
        return '#' . bin2hex($this->toASN1()->toDER());
    }

    public function equalityMatchingRule(): MatchingRule
    {
        return new BinaryMatch();
    }

    public function rfc2253String(): string
    {
        return $this->stringValue();
    }

    protected function _transcodedString(): string
    {
        return $this->stringValue();
    }
}
