<?php

declare(strict_types=1);

namespace OpenStack\Images\v2;

use OpenStack\Common\Api\AbstractParams;
use Psr\Http\Message\StreamInterface;

class Params extends AbstractParams
{
    public function imageName(): array
    {
        return array_merge($this->name('image'), [
            'description' => 'Name for the image. The name of an image is not unique to an Image service node. The '.
                             'API cannot expect users to know the names of images owned by others.',
            'required' => true,
        ]);
    }

    public function visibility(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::STRING_TYPE,
            'description' => 'Image visibility. Public or private. Default is public.',
            'enum'        => ['private', 'public', 'community', 'shared'],
        ];
    }

    public function tags(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::ARRAY_TYPE,
            'description' => 'Image tags',
            'items'       => ['type' => self::STRING_TYPE],
        ];
    }

    public function containerFormat(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'container_format',
            'description' => 'Format of the container. A valid value is ami, ari, aki, bare, ovf, or ova.',
            'enum'        => ['ami', 'ari', 'aki', 'bare', 'ovf', 'ova'],
        ];
    }

    public function diskFormat(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'disk_format',
            'description' => 'Format of the container. A valid value is ami, ari, aki, bare, ovf, or ova.',
            'enum'        => ['ami', 'ari', 'aki', 'vhd', 'vmdk', 'raw', 'qcow2', 'vdi', 'iso'],
        ];
    }

    public function minDisk(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::INT_TYPE,
            'sentAs'      => 'min_disk',
            'description' => 'Amount of disk space in GB that is required to boot the image.',
        ];
    }

    public function minRam(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::INT_TYPE,
            'sentAs'      => 'min_ram',
            'description' => 'Amount of RAM in GB that is required to boot the image.',
        ];
    }

    public function protectedParam(): array
    {
        return [
            'location'    => self::JSON,
            'type'        => self::BOOL_TYPE,
            'description' => 'If true, image is not deletable.',
        ];
    }

    public function queryName(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Shows only images with this name. A valid value is the name of the image as a string.',
        ];
    }

    public function queryVisibility(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Shows only images with this image visibility value or values.',
            'enum'        => ['public', 'private', 'shared'],
        ];
    }

    public function queryMemberStatus(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Shows only images with this member status.',
            'enum'        => ['accepted', 'pending', 'rejected', 'all'],
        ];
    }

    public function queryOwner(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Shows only images that are shared with this owner.',
        ];
    }

    public function queryStatus(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Shows only images with this image status.',
            'enum'        => ['queued', 'saving', 'active', 'killed', 'deleted', 'pending_delete'],
        ];
    }

    public function querySizeMin(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::INT_TYPE,
            'description' => 'Shows only images with this minimum image size.',
        ];
    }

    public function querySizeMax(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::INT_TYPE,
            'description' => 'Shows only images with this maximum image size.',
        ];
    }

    public function queryTag(): array
    {
        return [
            'location'    => self::QUERY,
            'type'        => self::STRING_TYPE,
            'description' => 'Image tag.',
        ];
    }

    public function contentType(): array
    {
        return [
            'location' => self::HEADER,
            'type'     => self::STRING_TYPE,
            'sentAs'   => 'Content-Type',
        ];
    }

    public function patchDoc(): array
    {
        return [
            'location'   => self::RAW,
            'type'       => self::STRING_TYPE,
            'required'   => true,
            'documented' => false,
        ];
    }

    public function data(): array
    {
        return [
            'location'   => self::RAW,
            'type'       => StreamInterface::class,
            'required'   => true,
            'documented' => false,
        ];
    }

    public function memberId(): array
    {
        return [
            'location'   => self::JSON,
            'sentAs'     => 'member',
            'type'       => self::STRING_TYPE,
            'documented' => false,
        ];
    }

    public function status(): array
    {
        return [
            'location' => self::JSON,
            'type'     => self::STRING_TYPE,
            'enum'     => ['pending', 'accepted', 'rejected'],
        ];
    }
}
