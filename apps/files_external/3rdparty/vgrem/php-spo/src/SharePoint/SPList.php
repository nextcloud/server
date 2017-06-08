<?php
    
namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionInvokeGetMethod;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\FormatType;
use Office365\PHP\Client\Runtime\OData\ODataPayload;
use Office365\PHP\Client\Runtime\ResourcePathEntity;


/**
 * Represents a SharePoint List resource.
 */
class SPList extends SecurableObject
{
    /**
     * The recommended way to add a list item is to send a POST request to the ListItemCollection resource endpoint, as shown in ListItemCollection request examples.
     * @param array $listItemCreationInformation Creation information for a List item
     * @return ListItem List Item resource
     * @throws \Exception
     */
    public function addItem(array $listItemCreationInformation)
    {

        $items = new ListItemCollection($this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"items"));
        $listItem = new ListItem($this->getContext());
        $listItem->parentCollection = $items;
        $listItem->setProperty('ParentList',$this,false);
        foreach($listItemCreationInformation as $key => $value){
            $listItem->setProperty($key,$value);
        }
        $qry = new ClientActionCreateEntity($items,$listItem);
        $this->getContext()->addQuery($qry,$listItem);
        return $listItem;
    }

    /**
     * Returns the list item with the specified list item identifier.
     * @param integer $id  SPList Item id
     * @return ListItem  List Item resource
     * @throws \Exception
     */
    public function getItemById($id)
    {
        return new ListItem(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"items({$id})")
        );
    }


    /**
     * Returns a collection of items from the list based on the specified query.
     * @param CamlQuery $camlQuery
     * @return ListItemCollection
     */
    public function getItems(CamlQuery $camlQuery = null)
    {
        $items = new ListItemCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"items"));
        if(isset($camlQuery)){
            $qry = new ClientActionInvokePostMethod(
                $this,
                "GetItems",
                null,
                $camlQuery->toQueryPayload()
            );
            $this->getContext()->addQuery($qry,$items);
        }
        return $items;
    }


    /**
     * Updates a list resource
     */
    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry);
    }

    /**
     * The recommended way to delete a list is to send a DELETE request to the List resource endpoint, as shown in List request examples.
     */
    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
        $this->removeFromParentCollection();
    }


    /**
     * Gets the set of permissions for the specified user
     * @param string $loginName
     * @return BasePermissions
     * @throws \Exception
     */
    public function getUserEffectivePermissions($loginName)
    {
        $permissions = new BasePermissions();
        $qry = new ClientActionInvokeGetMethod(
            $this,
            "GetUserEffectivePermissions",
            array(rawurlencode($loginName))
        );
        $this->getContext()->addQuery($qry,$permissions);
        return $permissions;
    }


    /**
     * @param ChangeLogItemQuery $query The query that contains the change token. Pass this parameter in the request body, as shown in the request example.
     * @return ListItemCollection
     */
    public function getListItemChangesSinceToken(ChangeLogItemQuery $query)
    {
        $result = new ListItemCollection($this->getContext());
        $qry = new ClientActionInvokePostMethod(
            $this,
            "getListItemChangesSinceToken",
            null,
            $query->toQueryPayload()
        );
        //$qry->ResponsePayloadFormatType = FormatType::Xml;
        $this->getContext()->addQuery($qry, $result);
        return $result;
    }


    /**
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
     * @return ContentTypeCollection
     */
    public function getContentTypes()
    {
        if(!$this->isPropertyAvailable('ContentTypes')){
            $this->setProperty("ContentTypes", new ContentTypeCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "ContentTypes")),false);
        }
        return $this->getProperty("ContentTypes");
    }


    /**
     * @return FieldCollection
     */
    public function getFields()
    {
        if(!$this->isPropertyAvailable('Fields')){
            $this->setProperty("Fields", new FieldCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "fields")));
        }
        return $this->getProperty("Fields");
    }

    /**
     * @return Folder
     */
    public function getRootFolder()
    {
        if(!$this->isPropertyAvailable('RootFolder')){
            $this->setProperty("RootFolder", new Folder($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "rootFolder")));
        }
        return $this->getProperty("RootFolder");
    }


    /**
     * @return ViewCollection
     */
    public function getViews()
    {
        if(!$this->isPropertyAvailable('Views')){
            $this->setProperty("Views",new ViewCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "views")));
        }
        return $this->getProperty("Views");
    }

    public function getInformationRightsManagementSettings()
    {
        if(!$this->isPropertyAvailable('InformationRightsManagementSettings')){
            $this->setProperty("InformationRightsManagementSettings", new InformationRightsManagementSettings($this->getContext(),$this->getResourcePath(), "InformationRightsManagementSettings"));
        }
        return $this->getProperty("InformationRightsManagementSettings");
    }


    /**
     * @return Web
     */
    public function getParentWeb()
    {
        if(!$this->isPropertyAvailable('ParentWeb')){
            $this->setProperty("ParentWeb", new Web($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "ParentWeb")));
        }
        return $this->getProperty("ParentWeb");
    }

    public function getEntityTypeName(){
        return "SP.List";
    }
}