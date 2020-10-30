<?php

namespace Psalm\Internal\Codebase;

use Psalm\Internal\DataFlow\DataFlowNode;
use function array_merge;
use function count;

class VariableUseGraph extends DataFlowGraph
{
    public function addNode(DataFlowNode $node) : void
    {
    }

    public function isVariableUsed(DataFlowNode $assignment_node) : bool
    {
        $visited_source_ids = [];

        $sources = [$assignment_node];

        for ($i = 0; count($sources) && $i < 100; $i++) {
            $new_sources = [];

            foreach ($sources as $source) {
                $visited_source_ids[$source->id] = true;

                $child_nodes = $this->getChildNodes(
                    $source,
                    $visited_source_ids
                );

                if ($child_nodes === null) {
                    return true;
                }

                $new_sources = array_merge(
                    $new_sources,
                    $child_nodes
                );
            }

            $sources = $new_sources;
        }

        return false;
    }

    /**
     * @param array<string, bool> $visited_source_ids
     * @return array<string, DataFlowNode>|null
     */
    private function getChildNodes(
        DataFlowNode $generated_source,
        array $visited_source_ids
    ) : ?array {
        $new_sources = [];

        if (!isset($this->forward_edges[$generated_source->id])) {
            return [];
        }

        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            $path_type = $path->type;

            if ($path->type === 'variable-use'
                || $path->type === 'closure-use'
                || $path->type === 'global-use'
                || $path->type === 'use-inside-instance-property'
                || $path->type === 'use-inside-static-property'
                || $path->type === 'use-inside-call'
                || $path->type === 'use-inside-conditional'
                || $path->type === 'use-inside-isset'
                || $path->type === 'arg'
            ) {
                return null;
            }

            if (isset($visited_source_ids[$to_id])) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'array', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'property', $generated_source->path_types)) {
                continue;
            }

            $new_destination = new DataFlowNode($to_id, $to_id, null);
            $new_destination->path_types = array_merge($generated_source->path_types, [$path_type]);

            $new_sources[$to_id] = $new_destination;
        }

        return $new_sources;
    }
}
