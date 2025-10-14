<?php

class Translates extends \DB\MySQLObject{
	static public $table = 'translates';

	static public function set_translate(string $table, string $field, int $field_id, string $language, string $value): int|bool {
		$param = [
			'table' => $table,
			'field' => $field,
			'field_id' => $field_id,
			'language' => $language,
			'value' => $value,
			'_mode' => \DB\Common::CSMODE_REPLACE,
		];
		return static::save($param);
	}

	static public function get_translate(string $table, string $field, int $field_id, string $language): string {
		$param = [
			'table' => $table,
			'field' => $field,
			'field_id' => $field_id,
			'language' => $language,
		];
		$data = static::get($param);
		if (empty($data)) {
			return '';
		}
		return $data['value']??'';
	}
}
