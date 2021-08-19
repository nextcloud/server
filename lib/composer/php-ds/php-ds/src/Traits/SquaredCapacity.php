<?php
namespace Ds\Traits;

/**
 * Common to structures that require a capacity which is a power of two.
 */
trait SquaredCapacity
{
    use Capacity;

    /**
     * Rounds an integer to the next power of two if not already a power of two.
     *
     * @param int $capacity
     *
     * @return int
     */
    private function square(int $capacity): int
    {
        return pow(2, ceil(log($capacity, 2)));
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
        $this->capacity = max($this->square($capacity), $this->capacity);
    }

    /**
     * Called when capacity should be increased to accommodate new values.
     */
    protected function increaseCapacity()
    {
        $this->capacity = $this->square(
            max(
                count($this) + 1,
                $this->capacity * $this->getGrowthFactor()
            )
        );
    }

    /**
     * @param int $total
     */
    protected function ensureCapacity(int $total)
    {
        while ($total > $this->capacity()) {
            $this->increaseCapacity();
        }
    }
}
