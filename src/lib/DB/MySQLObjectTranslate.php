<?php
/*
 * Creator: AKEB
 * Date: 27.05.2014 11:21:01
 * Encoding: UTF-8
 *
 */
namespace DB;

class MySQLObjectTranslate extends \DB\MySQLObject {

	static public $translates = true;

	static public function data($ref = false, $add = '', $field_list = '*', $no_sql_cache=false, $no_local_cache=false, $params=[]) {
		$data = parent::data($ref, $add, $field_list, $no_sql_cache, $no_local_cache, $params);
		if (!static::$translates) return $data;
		if (isset($data) && is_array($data) && $data) {
			$translate_data = \Translates::data(['table' => static::getTable()]);
			if (!$translate_data) return $data;

			if (isset($translate_data) && is_array($translate_data) && $translate_data) {
				$translate_hash = [];
				foreach ($translate_data as $translate_value) {
					if (!isset($translate_value['field_id'])) continue;
					if (!isset($translate_value['field'])) continue;
					if (!isset($translate_value['language'])) continue;
					if (!isset($translate_hash[$translate_value['field_id']])) {
						$translate_hash[$translate_value['field_id']] = [];
					}
					$translate_hash[$translate_value['field_id']][$translate_value['field'].'_'.$translate_value['language']] = $translate_value['value']??'';
					$currentLanguage = \T::getCurrentLanguage();
					if (isset($currentLanguage) && $currentLanguage == $translate_value['language']) {
						$translate_hash[$translate_value['field_id']][$translate_value['field']] = $translate_value['value']??'';
					}
				}
				if (!$translate_hash) return $data;
				foreach ($data as $key => $item) {
					if (!isset($item['id'])) continue;
					if (!isset($translate_hash[$item['id']])) continue;
					foreach($translate_hash[$item['id']] as $field => $value) {
						$data[$key][$field] = $value;
					}
				}
			}
		}
		return $data;
	}

	static public function get($ref = false, $add = '', $ref_name='id', $no_sql_cache=false, $no_local_cache=false, $params=[]) {
		$item = parent::get($ref, $add, $ref_name, $no_sql_cache, $no_local_cache, $params);
		if (!static::$translates) return $item;
		if (isset($item) && is_array($item) && $item && isset($item['id'])) {
			$translate_data = \Translates::data(['table' => static::getTable(), 'field_id' => $item['id']]);
			if (!$translate_data) return $item;

			if (isset($translate_data) && is_array($translate_data) && $translate_data) {
				$translate_hash = [];
				foreach ($translate_data as $translate_value) {
					if (!isset($translate_value['field_id'])) continue;
					if (!isset($translate_value['field'])) continue;
					if (!isset($translate_value['language'])) continue;
					if (!isset($translate_hash[$translate_value['field_id']])) {
						$translate_hash[$translate_value['field_id']] = [];
					}
					$translate_hash[$translate_value['field_id']][$translate_value['field'].'_'.$translate_value['language']] = $translate_value['value']??'';
					$currentLanguage = \T::getCurrentLanguage();
					if (isset($currentLanguage) && $currentLanguage == $translate_value['language']) {
						$translate_hash[$translate_value['field_id']][$translate_value['field']] = $translate_value['value']??'';
					}
				}
				if (!$translate_hash) return $item;
				if (!isset($translate_hash[$item['id']])) return $item;
				foreach($translate_hash[$item['id']] as $field => $value) {
					$item[$field] = $value;
				}
			}
		}
		return $item;
	}

	static public function save($param, $table_fields='', $ref_name='id', $add=''): int|bool {
		$fields_data = static::getDatabase()->getFields(static::getTable());
		$fields = [];
		if ($fields_data) {
			foreach($fields_data as $value) {
				if ($value instanceof \DB\Field) {
					if (isset($value) && isset($value->name) && $value->name) {
						$fields[] = $value->name;
					}
				}
			}
		}
		$new_param = [];
		$translate_param = [];
		foreach($param as $key => $value) {
			if (in_array($key, $fields) || strpos($key,'_') === 0) {
				$new_param[$key] = $value;
			} else {
				$translate_param[$key] = $value;
			}
		}
		$id = parent::save($new_param, $table_fields, $ref_name, $add);
		if (!$id) return $id;
		$translates = [];
		foreach($translate_param as $key => $value) {
			$language = \T::getCurrentLanguage();
			$keys = explode('_', $key);
			if ($keys && count($keys) > 1) {
				$language_test = end($keys);
				if (mb_strlen($language_test) == 2 && in_array($language_test, \T::getAvailableLanguages())) {
					$language = $language_test;
					unset($keys[count($keys) - 1]);
				}
			}
			$key = implode('_', $keys);
			$table = static::getTable();
			$field = $key;
			$field_id = $id;
			$uniq_key = $table.'_'.$field.'_'.$field_id.'_'.$language;

			$translates[$uniq_key] = [
				'table' => $table,
				'field' => $field,
				'field_id' => $field_id,
				'language' => $language,
				'value' => $value,
			];
		}
		if ($translates && is_array($translates)) {
			foreach($translates as $value) {
				\Translates::set_translate($value['table'], $value['field'], $value['field_id'], $value['language'], $value['value']);
			}
		}
		return $id;
	}

	static public function delete($ref=false, $add='', $ref_name='id') {
		$data = parent::data($ref,$add);
		if (isset($data) && is_array($data) && $data) {
			$ids = [];
			$ids = get_hash($data, 'id','id');
			if ($ids && is_array($ids)) {
				\Translates::delete(['table' => static::getTable(), 'field_id' => $ids]);
			}
		}
		return parent::delete($ref,$add,$ref_name);
	}

}