<?php

interface PermissionSubject_Interface {

	static public function permissions_hash(): array;
	static public function permissions_subject_hash(): array;

	static public function subject_hash(): array;

	static public function getUserPermissions(array $user): array;

}