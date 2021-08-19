<?php

namespace Sabre\VObject;

/**
 * FreeBusyData is a helper class that manages freebusy information.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class FreeBusyData
{
    /**
     * Start timestamp.
     *
     * @var int
     */
    protected $start;

    /**
     * End timestamp.
     *
     * @var int
     */
    protected $end;

    /**
     * A list of free-busy times.
     *
     * @var array
     */
    protected $data;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
        $this->data = [];

        $this->data[] = [
            'start' => $this->start,
            'end' => $this->end,
            'type' => 'FREE',
        ];
    }

    /**
     * Adds free or busytime to the data.
     *
     * @param int    $start
     * @param int    $end
     * @param string $type  FREE, BUSY, BUSY-UNAVAILABLE or BUSY-TENTATIVE
     */
    public function add($start, $end, $type)
    {
        if ($start > $this->end || $end < $this->start) {
            // This new data is outside our timerange.
            return;
        }

        if ($start < $this->start) {
            // The item starts before our requested time range
            $start = $this->start;
        }
        if ($end > $this->end) {
            // The item ends after our requested time range
            $end = $this->end;
        }

        // Finding out where we need to insert the new item.
        $currentIndex = 0;
        while ($start > $this->data[$currentIndex]['end']) {
            ++$currentIndex;
        }

        // The standard insertion point will be one _after_ the first
        // overlapping item.
        $insertStartIndex = $currentIndex + 1;

        $newItem = [
            'start' => $start,
            'end' => $end,
            'type' => $type,
        ];

        $precedingItem = $this->data[$insertStartIndex - 1];
        if ($this->data[$insertStartIndex - 1]['start'] === $start) {
            // The old item starts at the exact same point as the new item.
            --$insertStartIndex;
        }

        // Now we know where to insert the item, we need to know where it
        // starts overlapping with items on the tail end. We need to start
        // looking one item before the insertStartIndex, because it's possible
        // that the new item 'sits inside' the previous old item.
        if ($insertStartIndex > 0) {
            $currentIndex = $insertStartIndex - 1;
        } else {
            $currentIndex = 0;
        }

        while ($end > $this->data[$currentIndex]['end']) {
            ++$currentIndex;
        }

        // What we are about to insert into the array
        $newItems = [
            $newItem,
        ];

        // This is the amount of items that are completely overwritten by the
        // new item.
        $itemsToDelete = $currentIndex - $insertStartIndex;
        if ($this->data[$currentIndex]['end'] <= $end) {
            ++$itemsToDelete;
        }

        // If itemsToDelete was -1, it means that the newly inserted item is
        // actually sitting inside an existing one. This means we need to split
        // the item at the current position in two and insert the new item in
        // between.
        if (-1 === $itemsToDelete) {
            $itemsToDelete = 0;
            if ($newItem['end'] < $precedingItem['end']) {
                $newItems[] = [
                    'start' => $newItem['end'] + 1,
                    'end' => $precedingItem['end'],
                    'type' => $precedingItem['type'],
                ];
            }
        }

        array_splice(
            $this->data,
            $insertStartIndex,
            $itemsToDelete,
            $newItems
        );

        $doMerge = false;
        $mergeOffset = $insertStartIndex;
        $mergeItem = $newItem;
        $mergeDelete = 1;

        if (isset($this->data[$insertStartIndex - 1])) {
            // Updating the start time of the previous item.
            $this->data[$insertStartIndex - 1]['end'] = $start;

            // If the previous and the current are of the same type, we can
            // merge them into one item.
            if ($this->data[$insertStartIndex - 1]['type'] === $this->data[$insertStartIndex]['type']) {
                $doMerge = true;
                --$mergeOffset;
                ++$mergeDelete;
                $mergeItem['start'] = $this->data[$insertStartIndex - 1]['start'];
            }
        }
        if (isset($this->data[$insertStartIndex + 1])) {
            // Updating the start time of the next item.
            $this->data[$insertStartIndex + 1]['start'] = $end;

            // If the next and the current are of the same type, we can
            // merge them into one item.
            if ($this->data[$insertStartIndex + 1]['type'] === $this->data[$insertStartIndex]['type']) {
                $doMerge = true;
                ++$mergeDelete;
                $mergeItem['end'] = $this->data[$insertStartIndex + 1]['end'];
            }
        }
        if ($doMerge) {
            array_splice(
                $this->data,
                $mergeOffset,
                $mergeDelete,
                [$mergeItem]
            );
        }
    }

    public function getData()
    {
        return $this->data;
    }
}
