<?php


namespace Office365\PHP\Client\OneNote;


use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class Notebook extends ClientObject
{

    /**
     * @return UserRole
     */
    public function getUserRole()
    {
        if (!$this->isPropertyAvailable("UserRole")) {
            $this->setProperty("UserRole",
                new UserRole($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "UserRole"
                )));
        }
        return $this->getProperty("UserRole");
    }



    /**
     * @return NotebookLinks
     */
    public function getNotebookLinks()
    {
        if (!$this->isPropertyAvailable("NotebookLinks")) {
            $this->setProperty("NotebookLinks",
                new NotebookLinks($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "NotebookLinks"
                )));
        }
        return $this->getProperty("NotebookLinks");
    }

}