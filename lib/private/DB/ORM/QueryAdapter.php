<?php

namespace OC\DB\ORM;

use Doctrine\ORM\Query;
use OCP\DB\ORM\IQuery;

class QueryAdapter implements IQuery {
	private Query $query;

	public function __construct(Query $query) {
		$this->query = $query;
	}
}
