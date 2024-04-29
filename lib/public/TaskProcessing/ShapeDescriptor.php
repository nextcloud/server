<?php

namespace OCP\TaskProcessing;

class ShapeDescriptor {
	public function __construct(
		private string $name,
		private string $description,
		private EShapeType $shapeType,
	) {
	}

	public function getName(): string {
		return $this->name;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getShapeType(): EShapeType {
		return $this->shapeType;
	}
}
