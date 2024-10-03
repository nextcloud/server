<?php

namespace OCA\SocialLogin\Service;

use Hybridauth\Storage\StorageInterface;
use OCP\ISession;

class SessionStorage implements StorageInterface
{
    /** @var ISession */
    private $session;

    public function __construct(ISession $session)
    {
        $this->session = $session;
    }

    /**
    * {@inheritdoc}
    */
    public function get($key)
    {
        return $this->session->get($key);
    }

    /**
    * {@inheritdoc}
    */
    public function set($key, $value)
    {
        $this->session->set($key, $value);
    }

    /**
    * {@inheritdoc}
    */
    public function delete($key)
    {
        $this->session->remove($key);
    }

    /**
    * {@inheritdoc}
    */
    public function deleteMatch($key)
    {
        foreach ($this->session as $k => $v) {
            if (strstr($k, $key)) {
                $this->delete($k);
            }
        }
    }

    /**
    * {@inheritdoc}
    */
    public function clear()
    {
        $this->session->clear();
    }
}
