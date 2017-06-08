<?php

namespace WebChemistry\Doctrine;

class PDOStatementStub {

	/** @var \PDOStatement */
	private $stmt;

	public function __construct(\PDOStatement $stmt) {
		$this->stmt = $stmt;
	}

	public function nextRowset(): bool {
		return $this->stmt->nextRowset();
	}

	public function _closeCursor(): bool {
		return $this->stmt->closeCursor();
	}

	public function __call(string $name, array $arguments) {
		if ($name === 'closeCursor') {
			return TRUE;
		}

		return $this->stmt->$name(...$arguments);
	}

}
