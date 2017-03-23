<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionInvokeGetMethod;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientActionReadEntity;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\ClientValueObjectCollection;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\Runtime\ResourcePathServiceOperation;


/**
 * Represents a SharePoint site. A site is a type of SP.SecurableObject.
 * @property WebCollection Webs
 * @property FieldCollection Fields
 * @property ListCollection Lists
 */
class Web extends SecurableObject
{


    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry,$this);
    }

    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
        //$this->removeFromParentCollection();
    }


    /**
     * Returns the collection of all changes from the change log that have occurred within the scope of the site, based on the specified query.
     * @param ChangeQuery $query
     * @return ChangeCollection
     */
    public function getChanges(ChangeQuery $query)
    {
        $changes = new ChangeCollection($this->getContext());
        $qry = new ClientActionInvokePostMethod(
            $this,
            "GetChanges",
            null,
            $query->toQueryPayload()
        );
        $this->getContext()->addQuery($qry,$changes);
        return $changes;
    }


    /**
     * Gets the collection of all lists that are contained in the Web site available to the current user
     * based on the permissions of the current user.
     * @return ListCollection
     */
    public function getLists()
    {
        if(!$this->isPropertyAvailable('Lists')){
            $this->setProperty("Lists", new ListCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"Lists")));
        }
        return $this->getProperty("Lists");
    }

    /**
     * Gets a Web site collection object that represents all Web sites immediately beneath the Web site,
     * excluding children of those Web sites.
     * @return WebCollection
     */
    public function getWebs()
    {
        if(!$this->isPropertyAvailable('Webs')){
            $this->setProperty("Webs", new WebCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"webs")));
        }
        return $this->getProperty("Webs");
    }

    /**
     * Gets the collection of field objects that represents all the fields in the Web site.
     * @return FieldCollection
     */
    public function getFields()
    {
        if(!$this->isPropertyAvailable('Fields')){
            $this->setProperty("Fields", new FieldCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"fields")));
        }
        return $this->getProperty("Fields");
    }

    /**
     * Gets the collection of all first-level folders in the Web site.
     * @return FolderCollection
     */
    public function getFolders()
    {
        if(!isset($this->Folders)){
            $this->Folders = new FolderCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"folders"));
        }
        return $this->Folders;
    }


    /**
     * Gets the collection of all users that belong to the site collection.
     * @return UserCollection
     */
    public function getSiteUsers()
    {
        if(!isset($this->SiteUsers)){
            $this->SiteUsers = new UserCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"siteusers"));
        }
        return $this->SiteUsers;
    }


    /**
     * Gets the collection of groups for the site collection.
     * @return mixed|null|GroupCollection
     */
    public function getSiteGroups()
    {
        if(!isset($this->SiteGroups)){
            $this->setProperty("SiteGroups", new GroupCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"sitegroups")));
        }
        return $this->getProperty("SiteGroups");
    }

    
    /**
     * Gets the collection of role definitions for the Web site.
     * @return RoleAssignmentCollection
     */
    public function getRoleDefinitions()
    {
        if(!$this->isPropertyAvailable('RoleDefinitions')){
            $this->setProperty("RoleDefinitions", new RoleDefinitionCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"roledefinitions")));
        }
        return $this->getProperty("RoleDefinitions");
    }


    /**
     * Gets a value that specifies the collection of user custom actions for the site.
     * @return UserCustomActionCollection
     */
    public function getUserCustomActions()
    {
        if(!$this->isPropertyAvailable('UserCustomActions')){
            $this->setProperty("UserCustomActions", new UserCustomActionCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"UserCustomActions")));
        }
        return $this->getProperty("UserCustomActions");
    }


    /**
     * @return User
     */
    public function getCurrentUser()
    {
        if(!$this->isPropertyAvailable('CurrentUser')){
            $this->setProperty("CurrentUser", new User($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"CurrentUser")));
        }
        return $this->getProperty("CurrentUser");
    }


    /**
     * @return ClientValueObjectCollection
     */
    public function getSupportedUILanguageIds()
    {
        $value = new ClientValueObjectCollection("Collection(Edm.Int32)");
        $value->EntityName = "SupportedUILanguageIds";
        $qry = new ClientActionReadEntity($this->getResourceUrl() . "/SupportedUILanguageIds");
        $this->getContext()->addQuery($qry,$value);
        return $value;
    }

    /**
     * Returns the file object located at the specified server-relative URL.
     * @param string $serverRelativeUrl The server relative URL of the file.
     * @return File
     */
    public function getFileByServerRelativeUrl($serverRelativeUrl){
        $path = new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getfilebyserverrelativeurl",array(
            rawurlencode($serverRelativeUrl) 
        ));
        $file = new File($this->getContext(),$path);
        return $file;
    }

    /**
     * Returns the folder object located at the specified server-relative URL.
     * @param string $serverRelativeUrl The server relative URL of the folder.
     * @return Folder
     */
    public function getFolderByServerRelativeUrl($serverRelativeUrl){
        return new Folder(
            $this->getContext(),
            new ResourcePathServiceOperation(
                $this->getContext(),
                $this->getResourcePath(),
                "getfolderbyserverrelativeurl",
                array(
                    rawurlencode($serverRelativeUrl)
                )
            )
        );
    }

    /**
     * @return ContentTypeCollection
     */
    public function getContentTypes()
    {
        if(!$this->isPropertyAvailable('ContentTypes')){
            $this->setProperty(
                'ContentTypes',
                new ContentTypeCollection(
                    $this->getContext(),
                    new ResourcePathEntity(
                        $this->getContext(),
                        $this->getResourcePath(),
                        'ContentTypes'
                    )
                ),
                false
            );
        }
        return $this->getProperty('ContentTypes');
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $persistChanges
     */
    public function setProperty($name, $value, $persistChanges = true)
    {
        parent::setProperty($name, $value, $persistChanges);
        if ($name === 'Id') {
            $this->setResourceUrl("Site/openWebById(guid'{$value}')");
        }
    }
}
