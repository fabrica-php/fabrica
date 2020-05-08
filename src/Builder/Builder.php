<?php declare(strict_types=1);

namespace Noj\Fabrica\Builder;

use Noj\Fabrica\Definition;
use Throwable;
use function Noj\Dot\set;

class Builder
{
	private $class;
	private $definition;
	private $instances = 1;
	private $defineArguments = [];

	private $onComplete = [];

	/** @var Result[] */
	private static $created = [];
	private static $createdStack = [];

	public function __construct(string $class, Definition $definition)
	{
		$this->class = $class;
		$this->definition = $definition;
	}

	public function instances(int $instances): self
	{
		$this->instances = $instances;
		return $this;
	}

	public function defineArguments(array $defineArguments): self
	{
		$this->defineArguments = $defineArguments;
		return $this;
	}

	public function onComplete(callable $onComplete)
	{
		$this->onComplete[] = $onComplete;
		return $this;
	}

	public function create(callable $overrides = null)
	{
		try {
			if ($this->instances === 1) {
				return $this->createEntity($overrides);
			}

			return array_map(function () use ($overrides) {
				return $this->createEntity($overrides);
			}, range(1, $this->instances));
		} catch (Throwable $throwable) {
			self::$created = [];
			self::$createdStack = [];
			throw $throwable;
		}
	}

	private function createEntity(callable $overrides = null)
	{
		if (isset(self::$createdStack[$this->class])) {
			return self::$createdStack[$this->class];
		}

		$entity = new $this->class;
		self::$created[] = new Result($this->definition, $entity);
		self::$createdStack[$this->class] = $entity;

		$attributes = $this->definition->getAttributes($overrides, ...$this->defineArguments);
		set($entity, $attributes);

		unset(self::$createdStack[$this->class]);

		if (empty(self::$createdStack)) {
			$this->cleanUp();
		}

		return $entity;
	}

	private function cleanUp()
	{
		foreach (self::$created as $result) {
			$result->definition->fireCallbacks($result->entity, ...$this->defineArguments);
		}

		foreach ($this->onComplete as $handler) {
			$handler(self::$created);
		}

		self::$created = [];
	}
}