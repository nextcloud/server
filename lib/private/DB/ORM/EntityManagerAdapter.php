<?php

namespace OC\DB\ORM;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use OC\DB\ConnectionAdapter;
use OCP\DB\ORM\IQuery;
use OCP\DB\ORM\IEntityManager;
use OCP\DB\ORM\IEntityRepository;
use OCP\IDBConnection;

class EntityManagerAdapter implements IEntityManager {

	private EntityManager $em;
	private ConnectionAdapter $connection;

	public function __construct(ConnectionAdapter $connection) {
		$paths = array_filter(array_map(fn ($appId) => \OC_App::getAppPath($appId) . '/lib/Entity/', \OC_App::getEnabledApps()), fn ($path) => is_dir($path));
		$isDevMode = true;
		$proxyDir = null;
		$cache = null;


		$evm = $connection->getInner()->getEventManager();
		$tablePrefix = new TablePrefix('oc_');

		$evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $tablePrefix);
		// TODO actually use our cache with a psr6 cache wrapper or at least our cache config
		$config = ORMSetup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache);

		$this->em = EntityManager::create($connection->getInner(), $config, $evm);
		$this->connection = $connection;
	}

	public function createQuery($dql = ''): IQuery
	{
		return new QueryAdapter($this->em->createQuery($dql));
	}

	public function flush(): void {
		$this->em->flush();
	}

	public function find(string $className, $id, ?int $lockMode = null, ?int $lockVersion = null): ?object {
		return $this->em->find($className, $id, $lockMode, $lockVersion);
	}

	public function clear(): void {
		$this->em->clear();
	}

	public function persist(object $entity): void {
		$this->em->persist($entity);
	}

	public function remove(object $entity): void {
		$this->em->remove($entity);
	}

	public function lock(object $entity, int $lockMode, $lockVersion = null): void {
		$this->em->lock($entity, $lockMode, $lockVersion);
	}

	public function getRepository($className): IEntityRepository {
		/** @var EntityRepository $internalRepo */
		$internalRepo = $this->em->getRepository($className);
		return new EntityRepositoryAdapter($internalRepo);
	}

	public function contains(object $entity): bool {
		return $this->em->contains($entity);
	}

	public function getConnection(): IDBConnection {
		return $this->connection;
	}

	/**
	 * Only for internal use
	 */
	public function get(): EntityManager {
		return $this->em;
	}

}
