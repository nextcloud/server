<?php

namespace OC\DB\ORM;

use Doctrine\ORM\EntityRepository;
use OCP\DB\ORM\IEntityRepository;

class EntityRepositoryAdapter implements IEntityRepository
{
	private EntityRepository $entityRepository;

	public function __construct(EntityRepository $entityRepository) {
		$this->entityRepository = $entityRepository;
	}
}
