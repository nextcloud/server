<?php
namespace Aws;


trait HasMonitoringEventsTrait
{
    private $monitoringEvents = [];

    /**
     * Get client-side monitoring events attached to this object. Each event is
     * represented as an associative array within the returned array.
     *
     * @return array
     */
    public function getMonitoringEvents()
    {
        return $this->monitoringEvents;
    }

    /**
     * Prepend a client-side monitoring event to this object's event list
     *
     * @param array $event
     */
    public function prependMonitoringEvent(array $event)
    {
        array_unshift($this->monitoringEvents, $event);
    }

    /**
     * Append a client-side monitoring event to this object's event list
     *
     * @param array $event
     */
    public function appendMonitoringEvent(array $event)
    {
        $this->monitoringEvents []= $event;
    }
}
