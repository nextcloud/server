<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

/**
 * Trait DataStoreTrait
 */
trait DataStoreTrait
{
    /**
     * Returns storage instance
     *
     * @return \Hybridauth\Storage\StorageInterface
     */
    abstract public function getStorage();

    /**
     * Store a piece of data in storage.
     *
     * This method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it
     * can be also used by providers to store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $name
     * @param mixed $value
     */
    protected function storeData($name, $value = null)
    {
        // if empty, we simply delete the thing as we'd want to only store necessary data
        if (empty($value)) {
            $this->deleteStoredData($name);
        }

        $this->getStorage()->set($this->providerId . '.' . $name, $value);
    }

    /**
     * Retrieve a piece of data from storage.
     *
     * This method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it
     * can be also used by providers to retrieve from store any other useful data (i.g., user_id,
     * auth_nonce, etc.)
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getStoredData($name)
    {
        return $this->getStorage()->get($this->providerId . '.' . $name);
    }

    /**
     * Delete a stored piece of data.
     *
     * @param string $name
     */
    protected function deleteStoredData($name)
    {
        $this->getStorage()->delete($this->providerId . '.' . $name);
    }

    /**
     * Delete all stored data of the instantiated adapter
     */
    protected function clearStoredData()
    {
        $this->getStorage()->deleteMatch($this->providerId . '.');
    }
}
