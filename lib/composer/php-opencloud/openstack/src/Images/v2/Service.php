<?php

declare(strict_types=1);

namespace OpenStack\Images\v2;

use OpenStack\Common\Service\AbstractService;
use OpenStack\Images\v2\Models\Image;

/**
 * @property Api $api
 */
class Service extends AbstractService
{
    public function createImage(array $data): Image
    {
        return $this->model(Image::class)->create($data);
    }

    public function listImages(array $data = []): \Generator
    {
        return $this->model(Image::class)->enumerate($this->api->getImages(), $data);
    }

    /**
     * @param null $id
     */
    public function getImage($id = null): Image
    {
        $image = $this->model(Image::class);
        $image->populateFromArray(['id' => $id]);

        return $image;
    }
}
