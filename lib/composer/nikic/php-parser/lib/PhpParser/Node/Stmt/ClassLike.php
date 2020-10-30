<?php declare(strict_types=1);

namespace PhpParser\Node\Stmt;

use PhpParser\Node;

/**
 * @property Node\Name $namespacedName Namespaced name (if using NameResolver)
 */
abstract class ClassLike extends Node\Stmt
{
    /** @var Node\Identifier|null Name */
    public $name;
    /** @var Node\Stmt[] Statements */
    public $stmts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public $attrGroups;

    /**
     * @return TraitUse[]
     */
    public function getTraitUses() : array {
        $traitUses = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                $traitUses[] = $stmt;
            }
        }
        return $traitUses;
    }

    /**
     * @return ClassConst[]
     */
    public function getConstants() : array {
        $constants = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof ClassConst) {
                $constants[] = $stmt;
            }
        }
        return $constants;
    }

    /**
     * @return Property[]
     */
    public function getProperties() : array {
        $properties = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Property) {
                $properties[] = $stmt;
            }
        }
        return $properties;
    }

    /**
     * Gets property with the given name defined directly in this class/interface/trait.
     *
     * @param string $name Name of the property
     *
     * @return Property|null Property node or null if the property does not exist
     */
    public function getProperty(string $name) {
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop instanceof PropertyProperty && $name === $prop->name->toString()) {
                        return $stmt;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Gets all methods defined directly in this class/interface/trait
     *
     * @return ClassMethod[]
     */
    public function getMethods() : array {
        $methods = [];
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $methods[] = $stmt;
            }
        }
        return $methods;
    }

    /**
     * Gets method with the given name defined directly in this class/interface/trait.
     *
     * @param string $name Name of the method (compared case-insensitively)
     *
     * @return ClassMethod|null Method node or null if the method does not exist
     */
    public function getMethod(string $name) {
        $lowerName = strtolower($name);
        foreach ($this->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $lowerName === $stmt->name->toLowerString()) {
                return $stmt;
            }
        }
        return null;
    }
}
