<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *UserNotice* ASN.1 type used by 'Certificate Policies' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.4
 */
final class UserNoticeQualifier extends PolicyQualifierInfo
{
    private function __construct(
        private readonly ?DisplayText $text,
        private readonly ?NoticeReference $ref
    ) {
        parent::__construct(self::OID_UNOTICE);
    }

    public static function create(?DisplayText $text = null, ?NoticeReference $ref = null): self
    {
        return new self($text, $ref);
    }

    /**
     * @return self
     */
    public static function fromQualifierASN1(UnspecifiedType $el): PolicyQualifierInfo
    {
        $seq = $el->asSequence();
        $ref = null;
        $text = null;
        $idx = 0;
        if ($seq->has($idx, Element::TYPE_SEQUENCE)) {
            $ref = NoticeReference::fromASN1($seq->at($idx++)->asSequence());
        }
        if ($seq->has($idx, Element::TYPE_STRING)) {
            $text = DisplayText::fromASN1($seq->at($idx)->asString());
        }
        return self::create($text, $ref);
    }

    /**
     * Whether explicit text is present.
     */
    public function hasExplicitText(): bool
    {
        return isset($this->text);
    }

    /**
     * Get explicit text.
     */
    public function explicitText(): DisplayText
    {
        if (! $this->hasExplicitText()) {
            throw new LogicException('explicitText not set.');
        }
        return $this->text;
    }

    /**
     * Whether notice reference is present.
     */
    public function hasNoticeRef(): bool
    {
        return isset($this->ref);
    }

    /**
     * Get notice reference.
     */
    public function noticeRef(): NoticeReference
    {
        if (! $this->hasNoticeRef()) {
            throw new LogicException('noticeRef not set.');
        }
        return $this->ref;
    }

    protected function qualifierASN1(): Element
    {
        $elements = [];
        if (isset($this->ref)) {
            $elements[] = $this->ref->toASN1();
        }
        if (isset($this->text)) {
            $elements[] = $this->text->toASN1();
        }
        return Sequence::create(...$elements);
    }
}
