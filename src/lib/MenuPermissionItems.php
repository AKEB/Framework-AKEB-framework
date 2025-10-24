<?php

use PhpParser\Node\NullableType;

class MenuPermissionItems implements \IteratorAggregate, \Countable {
	private array $items = [];

	public function add(\MenuPermissionItem $item): self {
		$this->items[] = $item;
		return $this;
	}

	// Добавление нескольких элементов
	public function addMultiple(array $items): self {
		foreach ($items as $item) {
			if ($item instanceof \MenuPermissionItem) {
				$this->items[] = $item;
			}
		}
		return $this;
	}

	public function __construct(array|\MenuPermissionItem $item_or_items=[]) {
		$this->items = [];
		if (is_array($item_or_items)) {
			$this->addMultiple($item_or_items);
		} else if ($item_or_items instanceof \MenuPermissionItem) {
			$this->add($item_or_items);
		}
	}

	// Получение элемента по индексу
	public function get(int $index): ?\MenuPermissionItem {
		return $this->items[$index] ?? null;
	}

	// Удаление элемента по индексу
	public function remove(int $index): bool {
		if (isset($this->items[$index])) {
			unset($this->items[$index]);
			$this->items = array_values($this->items); // Переиндексация
			return true;
		}
		return false;
	}

	// Получение всех элементов
	public function all(): array {
		return $this->items;
	}

	// Очистка коллекции
	public function clear(): self {
		$this->items = [];
		return $this;
	}

	// Реализация IteratorAggregate для использования в foreach
	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator($this->items);
	}

	// Реализация Countable
	public function count(): int {
		return count($this->items);
	}

	// Магический метод для доступа как к массиву
	public function offsetExists($offset): bool {
		return isset($this->items[$offset]);
	}

	public function offsetGet($offset): ?\MenuPermissionItem {
		return $this->items[$offset] ?? null;
	}

	public function offsetSet($offset, $value): void {
		if ($value instanceof \MenuPermissionItem) {
			if (is_null($offset)) {
				$this->items[] = $value;
			} else {
				$this->items[$offset] = $value;
			}
		}
	}

	public function offsetUnset($offset): void {
		unset($this->items[$offset]);
	}
}