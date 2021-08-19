<?php

declare(strict_types=1);

namespace OpenStack\Common\JsonSchema;

class JsonPatch
{
    const OP_ADD     = 'add';
    const OP_REPLACE = 'replace';
    const OP_REMOVE  = 'remove';

    public static function diff($src, $dest)
    {
        return (new static())->makeDiff($src, $dest);
    }

    public function makeDiff($srcStruct, $desStruct, string $path = ''): array
    {
        $changes = [];

        if (is_object($srcStruct)) {
            $changes = $this->handleObject($srcStruct, $desStruct, $path);
        } elseif (is_array($srcStruct)) {
            $changes = $this->handleArray($srcStruct, $desStruct, $path);
        } elseif ($srcStruct != $desStruct) {
            $changes[] = $this->makePatch(self::OP_REPLACE, $path, $desStruct);
        }

        return $changes;
    }

    protected function handleArray(array $srcStruct, array $desStruct, string $path): array
    {
        $changes = [];

        if ($diff = $this->arrayDiff($desStruct, $srcStruct)) {
            foreach ($diff as $key => $val) {
                if (is_object($val)) {
                    $changes = array_merge($changes, $this->makeDiff($srcStruct[$key], $val, $this->path($path, $key)));
                } else {
                    $op = array_key_exists($key, $srcStruct) && !in_array($srcStruct[$key], $desStruct, true)
                        ? self::OP_REPLACE : self::OP_ADD;
                    $changes[] = $this->makePatch($op, $this->path($path, $key), $val);
                }
            }
        } elseif ($srcStruct != $desStruct) {
            foreach ($srcStruct as $key => $val) {
                if (!in_array($val, $desStruct, true)) {
                    $changes[] = $this->makePatch(self::OP_REMOVE, $this->path($path, $key));
                }
            }
        }

        return $changes;
    }

    protected function handleObject(\stdClass $srcStruct, \stdClass $desStruct, string $path): array
    {
        $changes = [];

        if ($this->shouldPartiallyReplace($srcStruct, $desStruct)) {
            foreach ($desStruct as $key => $val) {
                if (!property_exists($srcStruct, $key)) {
                    $changes[] = $this->makePatch(self::OP_ADD, $this->path($path, $key), $val);
                } elseif ($srcStruct->$key != $val) {
                    $changes = array_merge($changes, $this->makeDiff($srcStruct->$key, $val, $this->path($path, $key)));
                }
            }
        } elseif ($this->shouldPartiallyReplace($desStruct, $srcStruct)) {
            foreach ($srcStruct as $key => $val) {
                if (!property_exists($desStruct, $key)) {
                    $changes[] = $this->makePatch(self::OP_REMOVE, $this->path($path, $key));
                }
            }
        }

        return $changes;
    }

    protected function shouldPartiallyReplace(\stdClass $o1, \stdClass $o2): bool
    {
        // NOTE: count(stdClass) always returns 1
        return count(array_diff_key((array) $o1, (array) $o2)) < 1;
    }

    protected function arrayDiff(array $a1, array $a2): array
    {
        $result = [];

        foreach ($a1 as $key => $val) {
            if (!in_array($val, $a2, true)) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    protected function path(string $root, $path): string
    {
        $path = (string) $path;

        if ('_empty_' === $path) {
            $path = '';
        }

        return rtrim($root, '/').'/'.ltrim($path, '/');
    }

    protected function makePatch(string $op, string $path, $val = null): array
    {
        switch ($op) {
            default:
                return ['op' => $op, 'path' => $path, 'value' => $val];
            case self::OP_REMOVE:
                return ['op' => $op, 'path' => $path];
        }
    }
}
