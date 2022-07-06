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

	public function find($id) {
		return $this->entityRepository->find($id);
	}

	public function findAll() {
		return $this->entityRepository->findAll();
	}

	public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null) {
		return $this->entityRepository->findBy($criteria, $orderBy, $limit, $offset);
	}

	public function findOneBy(array $criteria) {
		return $this->entityRepository->findOneBy($criteria);
	}

	public function getClassName() {
		return $this->entityRepository->getClassName();
	}
}
