<?php
namespace Psalm\SourceControl\Git;

/**
 * Remote info.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class RemoteInfo
{
    /**
     * Remote name.
     *
     * @var null|string
     */
    protected $name;

    /**
     * Remote URL.
     *
     * @var null|string
     */
    protected $url;

    public function toArray() : array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
        ];
    }

    // accessor

    /**
     * Set remote name.
     *
     * @param string $name remote name
     *
     * @return $this
     */
    public function setName(string $name): RemoteInfo
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return remote name.
     *
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set remote URL.
     *
     * @param string $url remote URL
     *
     * @return $this
     */
    public function setUrl(string $url): RemoteInfo
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Return remote URL.
     *
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
