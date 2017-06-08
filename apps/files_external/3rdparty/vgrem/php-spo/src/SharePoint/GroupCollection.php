<?php
/**
 * Represents a collection of Group resources.
 */

namespace Office365\PHP\Client\SharePoint;

use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\ResourcePathServiceOperation;

/**
 * Represents a collection of Group resources.
 */
class GroupCollection extends ClientObjectCollection
{

    /**
     * Create a group
     * @param GroupCreationInformation $parameters
     * @return Group
     */
    public function add(GroupCreationInformation $parameters)
    {
        $group = new Group($this->getContext(), $this->getResourcePath());
        $qry = new ClientActionCreateEntity($this, $parameters);
        $this->getContext()->addQuery($qry, $group);
        $this->addChild($group);
        return $group;
    }

    /**
     * Returns a group from the collection based on the member ID of the group.
     * @param int $id The ID of the group to get.
     * @return Group The specified group.
     * @throws \Exception
     */
    public function getById($id)
    {
        $group = new Group(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(), $this->getResourcePath(), "getById", array($id))
        );
        return $group;
    }

    /**
     * Returns a cross-site group from the collection based on the name of the group.
     * @param string $name The name of the group. The group name is specified in its LoginName property.
     * @return Group
     * @throws \Exception
     */
    public function getByName($name)
    {
        $group = new Group(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(), $this->getResourcePath(), "getbyname", array($name))
        );
        return $group;
    }

    /**
     * Removes the group with the specified member ID from the collection.
     * @param int $id The ID of the group to remove.
     * @throws \Exception
     */
    public function removeById($id)
    {
        $qry = new ClientActionInvokePostMethod($this, "removebyid", array($id));
        $this->getContext()->addQuery($qry);
    }

    /**
     * Removes the cross-site group with the specified name from the collection.
     * @param string $groupName The name of the group to remove. The group name is specified in its LoginName property.
     * @throws \Exception
     */
    public function removeByLoginName($groupName)
    {
        $qry = new ClientActionInvokePostMethod($this, "removeByLoginName", array($groupName));
        $this->getContext()->addQuery($qry);
    }
}
