<?php
namespace Ds;

use UnderflowException;

/**
 * A PriorityQueue is very similar to a Queue. Values are pushed into the queue
 * with an assigned priority, and the value with the highest priority will
 * always be at the front of the queue.
 *
 * @package Ds
 */
final class PriorityQueue implements Collection
{
    use Traits\GenericCollection;
    use Traits\SquaredCapacity;

    /**
     * @var int
     */
    const MIN_CAPACITY = 8;

    /**
     * @var array
     */
    private $heap = [];

    /**
     * @var int
     */
    private $stamp = 0;

    /**
     * Creates a new instance.
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->heap     = [];
        $this->stamp    = 0;
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * @inheritDoc
     */
    public function copy(): self
    {
        $copy = new PriorityQueue();

        $copy->heap     = $this->heap;
        $copy->stamp    = $this->stamp;
        $copy->capacity = $this->capacity;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->heap);
    }

    /**
     * Returns the value with the highest priority in the priority queue.
     *
     * @return mixed
     *
     * @throw UnderflowException
     */
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return $this->heap[0]->value;
    }

    /**
     * Returns the index of a node's left leaf.
     *
     * @param int $index The index of the node.
     *
     * @return int The index of the left leaf.
     */
    private function left(int $index): int
    {
        return ($index * 2) + 1;
    }

    /**
     * Returns the index of a node's right leaf.
     *
     * @param int $index The index of the node.
     *
     * @return int The index of the right leaf.
     */
    private function right(int $index): int
    {
        return ($index * 2) + 2;
    }

    /**
     * Returns the index of a node's parent node.
     *
     * @param int $index The index of the node.
     *
     * @return int The index of the parent.
     */
    private function parent(int $index): int
    {
        return ($index - 1) / 2;
    }

    /**
     * Compares two indices of the heap.
     *
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    private function compare(int $a, int $b)
    {
        $x = $this->heap[$a];
        $y = $this->heap[$b];

        // Compare priority, using insertion stamp as fallback.
        return ($x->priority <=> $y->priority) ?: ($y->stamp <=> $x->stamp);
    }

    /**
     * Swaps the nodes at two indices of the heap.
     *
     * @param int $a
     * @param int $b
     */
    private function swap(int $a, int $b)
    {
        $temp           = $this->heap[$a];
        $this->heap[$a] = $this->heap[$b];
        $this->heap[$b] = $temp;
    }

    /**
     * Returns the index of a node's largest leaf node.
     *
     * @param int $parent the parent node.
     *
     * @return int the index of the node's largest leaf node.
     */
    private function getLargestLeaf(int $parent)
    {
        $left  = $this->left($parent);
        $right = $this->right($parent);

        if ($right < count($this->heap) && $this->compare($left, $right) < 0) {
            return $right;
        }

        return $left;
    }

    /**
     * Starts the process of sifting down a given node index to ensure that
     * the heap's properties are preserved.
     *
     * @param int $node
     */
    private function siftDown(int $node)
    {
        $last = floor(count($this->heap) / 2);

        for ($parent = $node; $parent < $last; $parent = $leaf) {

            // Determine the largest leaf to potentially swap with the parent.
            $leaf = $this->getLargestLeaf($parent);

            // Done if the parent is not greater than its largest leaf
            if ($this->compare($parent, $leaf) > 0) {
                break;
            }

            $this->swap($parent, $leaf);
        }
    }

    /**
     * Sets the root node and sifts it down the heap.
     *
     * @param PriorityNode $node
     */
    private function setRoot(PriorityNode $node)
    {
        $this->heap[0] = $node;
        $this->siftDown(0);
    }

    /**
     * Returns the root node of the heap.
     *
     * @return PriorityNode
     */
    private function getRoot(): PriorityNode
    {
        return $this->heap[0];
    }

    /**
     * Returns and removes the value with the highest priority in the queue.
     *
     * @return mixed
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        // Last leaf of the heap to become the new root.
        $leaf = array_pop($this->heap);

        if (empty($this->heap)) {
            return $leaf->value;
        }

        // Cache the current root value to return before replacing with next.
        $value = $this->getRoot()->value;

        // Replace the root, then sift down.
        $this->setRoot($leaf);
        $this->checkCapacity();

        return $value;
    }

    /**
     * Sifts a node up the heap until it's in the right position.
     *
     * @param int $leaf
     */
    private function siftUp(int $leaf)
    {
        for (; $leaf > 0; $leaf = $parent) {
            $parent = $this->parent($leaf);

            // Done when parent priority is greater.
            if ($this->compare($leaf, $parent) < 0) {
                break;
            }

            $this->swap($parent, $leaf);
        }
    }

    /**
     * Pushes a value into the queue, with a specified priority.
     *
     * @param mixed $value
     * @param int   $priority
     */
    public function push($value, int $priority)
    {
        $this->checkCapacity();

        // Add new leaf, then sift up to maintain heap,
        $this->heap[] = new PriorityNode($value, $priority, $this->stamp++);
        $this->siftUp(count($this->heap) - 1);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $heap  = $this->heap;
        $array = [];

        while ( ! $this->isEmpty()) {
            $array[] = $this->pop();
        }

        $this->heap = $heap;
        return $array;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        while ( ! $this->isEmpty()) {
            yield $this->pop();
        }
    }
}

/**
 * @internal
 */
final class PriorityNode
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @var int
     */
    public $priority;

    /**
     * @var int
     */
    public $stamp;

    /**
     * @param mixed $value
     * @param int   $priority
     * @param int   $stamp
     */
    public function __construct($value, int $priority, int $stamp)
    {
        $this->value    = $value;
        $this->priority = $priority;
        $this->stamp    = $stamp;
    }
}
