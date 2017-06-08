<?php

declare(strict_types=1);

namespace WebChemistry\Doctrine;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class QueryCollection {

	/** @var bool */
	public static $debugMode = FALSE;

	/** @var \ReflectionMethod */
	private static $parseReflection;

	/** @var \ReflectionMethod */
	private static $paramsReflection;

	/** @var EntityManager */
	private $em;

	/** @var string */
	private $sql = '';

	/** @var array */
	private $results = [];

	/** @var int */
	private $index = -1;

	// metadata start

	/** @var array */
	private $parameters = [];

	/** @var array */
	private $types = [];

	/** @var Query[] */
	private $queries = [];

	/** @var int */
	private $collection = 0;

	public function __construct(EntityManager $em) {
		$this->em = $em;
		$this->results[$this->collection] = [];
	}

	// metadata end

	public function fromQueryBuilder(QueryBuilder $queryBuilder): \Generator {
		return $this->fromQuery($queryBuilder->getQuery());
	}

	public function fromQuery(Query $query): \Generator {
		$this->index++;
		if (self::$debugMode) { // Debug
			return $this->createDebugGenerator($query);
		}

		$this->queries[$this->index] = $query;
		$this->processQuery($query);

		return $this->createGenerator($this->collection, $this->index);
	}

	public function createGenerator(int $collection, int $index): \Generator {
		yield from $this->getResult($collection, $index);
	}

	public function createDebugGenerator(Query $query): \Generator {
		yield from $query->getResult($query->getHydrationMode());
	}

	public function exec(Query $query): void {
		$this->processQuery($query);
	}

	/**
	 * @return array
	 */
	protected function getResult(int $collection, int $index): array {
		if (!$this->results[$collection]) {
			$this->processQueries();
		}

		return $this->results[$collection][$index];
	}

	private function processQueries(): void {
		if (!$this->queries) {
			return;
		}
		/** @var PDOStatement|PDOStatementStub $mock */
		$mock = new PDOStatementStub($this->em->getConnection()->executeQuery($this->sql, $this->parameters, $this->types));
		$index = 0;
		do {
			if (!isset($this->queries[$index])) {
				continue;
			}
			$query = $this->queries[$index];

			// hydrate sql result
			$this->results[$this->collection][$index] = $this->em->newHydrator($hydrationMode = $query->getHydrationMode())
				->hydrateAll($mock, self::getParser($query)->getResultSetMapping(), $query->getHints());

			$index++;
		} while ($mock->nextRowset());

		$mock->_closeCursor();

		// reset - next collection
		$this->queries = [];
		$this->types = [];
		$this->parameters = [];
		$this->results[++$this->collection] = [];
		$this->index = 0;
		$this->sql = '';
	}

	private function processQuery(Query $query): void {
		if (!$this->em) {
			$this->em = $query->getEntityManager();
		}

		$mapping = self::processParamMappings($query);
		foreach ($mapping[0] as $i => $parameter) {
			$this->parameters[] = $parameter;
			$this->types[] = $mapping[1][$i];
		}

		$this->sql .= $query->getSQL() . ";\n";
	}

	// hacks

	private static function getParser(Query $query): Query\ParserResult {
		if (!self::$parseReflection) {
			self::$parseReflection = new \ReflectionMethod(Query::class, '_parse');
		}

		return self::$parseReflection->getClosure($query)->__invoke();
	}

	private static function processParamMappings(Query $query): array {
		if (!self::$paramsReflection) {
			self::$paramsReflection = new \ReflectionMethod(Query::class, 'processParameterMappings');
		}

		return self::$paramsReflection->getClosure($query)->__invoke(self::getParser($query)->getParameterMappings());
	}

}
