<?php


namespace Office365\PHP\Client\OutlookServices;

use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\Office365Version;
use Office365\PHP\Client\Runtime\OperationParameterCollection;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

/**
 * A user in the system.
 * The Me endpoint is provided as a shortcut for specifying the current user by SMTP address.
 */
class User extends ClientObject
{

    /**
     * @return MessageCollection
     */
    public function getMessages()
    {
        if (!$this->isPropertyAvailable("Messages")) {
            $this->setProperty("Messages",
                new MessageCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Messages"
                )));
        }
        return $this->getProperty("Messages");
    }

    /**
     * @param string $folderId
     * @return MailFolder
     */
    public function getFolder($folderId)
    {
        if (!$this->isPropertyAvailable("Folders")) {
            $this->setProperty("Folders",
                new MailFolder($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    $this->getFolderEntityName() . "/" . $folderId
                )));
        }
        return $this->getProperty("Folders");
    }

    /**
     * @return MailFolder
     */
    public function getFolders()
    {
        if (!$this->isPropertyAvailable("Folders")) {
            $this->setProperty("Folders",
                new MailFolder($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    $this->getFolderEntityName()
                )));
        }
        return $this->getProperty("Folders");
    }


    /**
     * @param Message $message
     * @param bool $saveToSentItems
     */
    public function sendEmail(Message $message, $saveToSentItems)
    {
        $payload = new OperationParameterCollection();
        $payload->add("Message", $message);
        $payload->add("SaveToSentItems", $saveToSentItems);
        $action = new ClientActionInvokePostMethod($this, "SendMail", null, $payload);
        $this->getContext()->addQuery($action);
    }

    /**
     * @return ContactCollection
     */
    public function getContacts()
    {
        if (!$this->isPropertyAvailable("Contacts")) {
            $this->setProperty("Contacts",
                new ContactCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Contacts"
                )));
        }
        return $this->getProperty("Contacts");
    }


    /**
     * @return EventCollection
     */
    public function getEvents()
    {
        if (!$this->isPropertyAvailable("Events")) {
            $this->setProperty("Events",
                new EventCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Events"
                )));
        }
        return $this->getProperty("Events");
    }


    /**
     * @return CalendarCollection
     */
    public function getCalendars()
    {
        if (!$this->isPropertyAvailable("Calendars")) {
            $this->setProperty("Calendars",
                new CalendarCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Calendars"
                )));
        }
        return $this->getProperty("Calendars");
    }


    /**
     * @return CalendarGroupCollection
     */
    public function getCalendarGroups()
    {
        if (!$this->isPropertyAvailable("CalendarGroups")) {
            $this->setProperty("CalendarGroups",
                new CalendarGroupCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "CalendarGroups"
                )));
        }
        return $this->getProperty("CalendarGroups");
    }


    /**
     * @return SubscriptionCollection
     */
    public function getSubscriptions()
    {
        if (!$this->isPropertyAvailable("Subscriptions")) {
            $this->setProperty("Subscriptions",
                new SubscriptionCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Subscriptions"
                )));
        }
        return $this->getProperty("Subscriptions");
    }


    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        if (!$this->isPropertyAvailable("Calendar")) {
            $this->setProperty("Calendar",
                new Calendar(
                    $this->getContext(),
                    new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"Calendar")
                ));
        }
        return $this->getProperty("Calendar");
    }


    /**
     * @return string
     * @throws \Exception
     */
    private function getFolderEntityName()
    {
        if ($this->getContext()->getApiVersion() == Office365Version::V1)
            return "Folders";
        if ($this->getContext()->getApiVersion() == Office365Version::V2)
            return "MailFolders";

        throw new \Exception("Unknown API version '" . $this->getContext()->getApiVersion() . "'");
    }


    /**
     * The user's alias. Typically the SMTP address of the user.
     * @var string
     */
    public $Alias;


    /**
     * The user's display name.
     * @var string
     */
    public $DisplayName;


    /**
     * The user's primary calendar. Navigation property.
     * @var Calendar
     */
    public $Calendar;


    /**
     * The GUID assigned to the user's mailbox.
     * @var string
     */
    public $MailboxGuid;

    /**
     * The root folder of the user's mailbox.
     * @var MailFolder
     */
    public $RootFolder;
}