<?php
namespace Ds\Traits;

use Ds\Deque;

/**
 * Common to structures that deal with an internal capacity. While none of the
 * PHP implementations actually make use of a capacity, it's important to keep
 * consistent with the extension.
 */
trait Capacity
{
    /**
     * @var integer internal capacity
     */
    private $capacity = self::MIN_CAPACITY;

    /**
     * Returns the current capacity.
     *
     * @return int
     */
    public function capacity(): int
    {
        return $this->capacity;
    }

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity)
    {
        $this->capacity = max($capacity, $this->capacity);
    }

    /**
     * @return float the structures growth factor.
     */
    protected function getGrowthFactor(): float
    {
        return 2;
    }

    /**
     * @return float to multiply by when decreasing capacity.
     */
    protected function getDecayFactor(): float
    {
        return 0.5;
    }

    /**
     * @return float the ratio between size and capacity when capacity should be
     *               decreased.
     */
    protected function getTruncateThreshold(): float
    {
        return 0.25;
    }

    /**
     * Checks and adjusts capacity if required.
     */
    protected function checkCapacity()
    {
        if ($this->shouldIncreaseCapacity()) {
            $this->increaseCapacity();
        } else {
            if ($this->shouldDecreaseCapacity()) {
                $this->decreaseCapacity();
            }
        }
    }

    /**
     * @param int $total
     */
    protected function ensureCapacity(int $total)
    {
        if ($total > $this->capacity()) {
            $this->capacity = max($total, $this->nextCapacity());
        }
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldIncreaseCapacity(): bool
    {
        return $this->count() >= $this->capacity();
    }

    protected function nextCapacity(): int
    {
        return $this->capacity() * $this->getGrowthFactor();
    }

    /**
     * Called when capacity should be increased to accommodate new values.
     */
    protected function increaseCapacity()
    {
        $this->capacity = max(
            $this->count(),
            $this->nextCapacity()
        );
    }

    /**
     * Called when capacity should be decrease if it drops below a threshold.
     */
    protected function decreaseCapacity()
    {
        $this->capacity = max(
            self::MIN_CAPACITY,
            $this->capacity()  * $this->getDecayFactor()
        );
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldDecreaseCapacity(): bool
    {
        return count($this) <= $this->capacity() * $this->getTruncateThreshold();
    }
}
