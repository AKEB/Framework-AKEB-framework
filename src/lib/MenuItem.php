<?php

class MenuItem {
	private string $icon = '';
	private string $title = '';
	private string $link = '';
	private \MenuItems $children;
	private \MenuPermissionItems $permissions;
	private bool $active = false;

	/**
	 * __construct
	 *
	 * @param  string $icon
	 * @param  string $title
	 * @param  string $link
	 * @param  \MenuItems|null $children
	 * @param  \MenuPermissionItem|\MenuPermissionItems|null|array $permissions
	 * @return void
	 */
	public function __construct(string $icon, string $title, string $link = '', \MenuItems|null $children = null, \MenuPermissionItem|\MenuPermissionItems|null|array $permissions = null) {
		$this->icon = $icon;
		$this->title = $title;
		$this->link = $link;
		$this->children = isset($children) ? $children : new \MenuItems();
		if (!isset($permissions)) {
			$this->permissions = new \MenuPermissionItems();
		} elseif ($permissions instanceof \MenuPermissionItems) {
			$this->permissions = $permissions;
		} elseif ($permissions instanceof \MenuPermissionItem) {
			$this->permissions = new \MenuPermissionItems($permissions);
		} elseif (is_array($permissions)) {
			$this->permissions = new \MenuPermissionItems();
			$this->permissions->addMultiple($permissions);
		}
		$this->active = false;
	}

	// Геттеры
	public function getIcon(): string {
		return $this->icon;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getLink(): string {
		return $this->link;
	}

	public function getChildren(): \MenuItems {
		return $this->children;
	}

	public function getPermissions(): \MenuPermissionItems {
		return $this->permissions;
	}

	public function isActive(): bool {
		return $this->active;
	}

	// Сеттеры
	public function setIcon(string $icon): self {
		$this->icon = $icon;
		return $this;
	}

	public function setTitle(string $title): self {
		$this->title = $title;
		return $this;
	}

	public function setLink(string $link): self {
		$this->link = $link;
		return $this;
	}

	public function setChildren(\MenuItems $children): self {
		$this->children = $children;
		return $this;
	}

	public function setPermissions(\MenuPermissionItems $permissions): self {
		$this->permissions = $permissions;
		return $this;
	}

	public function setActive(bool $active): self {
		$this->active = $active;
		return $this;
	}

	// Добавление дочернего элемента

	public function addChild(\MenuItem $child): self {
		$this->children?->add($child);
		return $this;
	}

	public function addPermission(\MenuPermissionItem $permission): self {
		$this->permissions?->add($permission);
		return $this;
	}

	// Проверка наличия дочерних элементов
	public function hasChildren(): bool {
		return $this->children?->count() > 0;
	}
	public function hasPermissions(): bool {
		return $this->permissions?->count() > 0;
	}

}