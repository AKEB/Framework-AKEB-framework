<?php

class MenuPermissionItem {
	public string $permission = '';
	public int $subjectId = 0;
	public string $accessType = '';

	public function __construct(string $permission, int $subjectId=0, string $accessType=READ) {
		$this->permission = $permission;
		$this->subjectId = $subjectId;
		$this->accessType = $accessType;
	}

	public function getPermission(): string {
		return $this->permission;
	}

	public function getSubjectId(): int {
		return $this->subjectId;
	}

	public function getAccessType(): string {
		return $this->accessType;
	}

	public function setPermission(string $permission): self {
		$this->permission = $permission;
		return $this;
	}

	public function setSubjectId(int $subjectId): self {
		$this->subjectId = $subjectId;
		return $this;
	}

	public function setAccessType(string $accessType): self {
		$this->accessType = $accessType;
		return $this;
	}
}