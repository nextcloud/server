<?php

declare(strict_types=1);

namespace OpenStack\Images\v2;

class JsonPatch extends \OpenStack\Common\JsonSchema\JsonPatch
{
    public function disableRestrictedPropRemovals(array $diff, array $restrictedProps): array
    {
        foreach ($diff as $i => $changeSet) {
            if ('remove' == $changeSet['op'] && in_array($changeSet['path'], $restrictedProps)) {
                unset($diff[$i]);
            }
        }

        return $diff;
    }

    /**
     * {@inheritdoc}
     *
     * We need to override the proper way to handle objects because Glance v2 does not
     * support whole document replacement with empty JSON pointers.
     */
    protected function handleObject(\stdClass $srcStruct, \stdClass $desStruct, string $path): array
    {
        $changes = [];

        foreach ($desStruct as $key => $val) {
            if (!property_exists($srcStruct, $key)) {
                $changes[] = $this->makePatch(self::OP_ADD, $this->path($path, $key), $val);
            } elseif ($srcStruct->$key != $val) {
                $changes = array_merge($changes, $this->makeDiff($srcStruct->$key, $val, $this->path($path, $key)));
            }
        }

        if ($this->shouldPartiallyReplace($desStruct, $srcStruct)) {
            foreach ($srcStruct as $key => $val) {
                if (!property_exists($desStruct, $key)) {
                    $changes[] = $this->makePatch(self::OP_REMOVE, $this->path($path, $key));
                }
            }
        }

        return $changes;
    }

    protected function handleArray(array $srcStruct, array $desStruct, string $path): array
    {
        $changes = [];

        if ($srcStruct != $desStruct) {
            if ($diff = $this->arrayDiff($desStruct, $srcStruct)) {
                $changes[] = $this->makePatch(self::OP_REPLACE, $path, $desStruct);
            }
            foreach ($srcStruct as $key => $val) {
                if (!in_array($val, $desStruct, true)) {
                    $changes[] = $this->makePatch(self::OP_REMOVE, $this->path($path, $key));
                }
            }
        }

        return $changes;
    }
}
