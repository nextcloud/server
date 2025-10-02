<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\Name;

/**
 * Implements *directoryName* CHOICE type of *GeneralName*.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class DirectoryName extends GeneralName
{
    private function __construct(
        private readonly Name $directoryName
    ) {
        parent::__construct(self::TAG_DIRECTORY_NAME);
    }

    public static function create(Name $directoryName): self
    {
        return new self($directoryName);
    }

    /**
     * @return self
     */
    public static function fromChosenASN1(UnspecifiedType $el): GeneralName
    {
        return self::create(Name::fromASN1($el->asSequence()));
    }

    /**
     * Initialize from distinguished name string.
     */
    public static function fromDNString(string $str): self
    {
        return self::create(Name::fromString($str));
    }

    public function string(): string
    {
        return $this->directoryName->toString();
    }

    /**
     * Get directory name.
     */
    public function dn(): Name
    {
        return $this->directoryName;
    }

    protected function choiceASN1(): TaggedType
    {
        // Name type is itself a CHOICE, so explicit tagging must be
        // employed to avoid ambiguities
        return ExplicitlyTaggedType::create($this->tag, $this->directoryName->toASN1());
    }
}
