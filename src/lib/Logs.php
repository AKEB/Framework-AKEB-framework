<?php

class Logs extends \DB\MySQLObject{
	static public $table = 'logs';

	const ACTION_OTHER             = 1;
	const ACTION_CREATE            = 2;
	const ACTION_UPDATE            = 3;
	const ACTION_DELETE            = 4;
	const ACTION_LOGIN             = 5;
	const ACTION_LOGOUT            = 6;
	const ACTION_START_IMPERSONATE = 7;
	const ACTION_STOP_IMPERSONATE  = 8;

	static private array $objects = [];


	static public function action_hash() {
		$data = \LogActions::data();
		if (!$data) return [];
		return get_hash($data, 'id', 'title');
	}

	static public function object_hash() {
		return array_merge(static::get_default_objects(), static::$objects);
	}

	static private function get_default_objects() {
		return [
			\Groups::LOGS_OBJECT => \T::Framework_Logs_Objects_Group(),
			\ObjectPermissions::LOGS_OBJECT => \T::Framework_Logs_Objects_ObjectPermissions(),
			\Sessions::LOGS_OBJECT => \T::Framework_Logs_Objects_Session(),
			\UserGroups::LOGS_OBJECT => \T::Framework_Logs_Objects_UserGroup(),
			\Users::LOGS_OBJECT => \T::Framework_Logs_Objects_User(),
			\Notifications::LOGS_OBJECT => \T::Framework_Logs_Objects_Notification(),
		];
	}

	static public function add_object(string $object, string $title): void {
		static::$objects[$object] = $title;
	}

	static public function log(string $code, int $action=0, string $object='', int $object_id=0, array $json_data=[], string $comment='', int $original_user_id=0, int $user_id=0): int|bool {
		if (!$user_id) {
			$user_id = \Sessions::currentUserId();
		}
		if (!$original_user_id) {
			$original_user_id = \Sessions::originalUserId();
		}

		$bt = debug_backtrace();
		$bt = array_reverse($bt);
		$t = [];
		foreach ($bt as $v) {
			if (isset($v['file']) && $v['file'] && $v['file'] == __FILE__) continue;
			$filename = $v['file']??'';
			$line = intval($v['line']??0);
			$class = $v['class']??'';
			$function = $v['function']??'';
			$t[] = sprintf("%s:%d %s::%s", $filename, $line, $class, $function);
		}
		$trace = implode("\n",$t);
		$param = [
			'user_id' => $user_id,
			'original_user_id' => $original_user_id,
			'code' => $code,
			'action' => $action,
			'object' => $object,
			'object_id' => $object_id,
			'json_data' => json_encode($json_data),
			'comment' => strval($comment),
			'time' => time(),
			'trace' => $trace,
			'_mode' => \DB\Common::CSMODE_INSERT,
		];
		return static::save($param);
	}

	static public function update_log(string $object='', int $object_id=0, array $old_data=[], array $new_data=[], array $json_data=[], string $comment='', int $original_user_id=0, int $user_id=0): int|bool {
		$code = $object.'_update';
		if (!isset($json_data['data'])) $json_data['data'] = [];
		foreach($old_data as $key => $value) {
			if (!isset($json_data['data'][$key])) $json_data['data'][$key] = ['old' => '', 'new' => ''];
			$json_data['data'][$key]['old'] = $value;
		}
		foreach($new_data as $key => $value) {
			if (!isset($json_data['data'][$key])) $json_data['data'][$key] = ['old' => '', 'new' => ''];
			$json_data['data'][$key]['new'] = $value;
		}
		foreach($json_data['data'] as $key => $value) {
			if ($value['old'] == $value['new']) {
				unset($json_data['data'][$key]);
			}
		}
		return static::log($code, static::ACTION_UPDATE, $object, $object_id, $json_data);
	}

	static public function delete_log(string $object='', int $object_id=0, array $old_data=[], array $json_data=[], string $comment='', int $original_user_id=0, int $user_id=0): int|bool {
		$code = $object.'_delete';
		if (!isset($json_data['data'])) $json_data['data'] = [];
		$json_data['data'] = $old_data;
		return static::log($code, static::ACTION_DELETE, $object, $object_id, $json_data);
	}

	static public function create_log(string $object='', int $object_id=0, array $new_data=[], array $json_data=[], string $comment='', int $original_user_id=0, int $user_id=0): int|bool {
		$code = $object.'_create';
		if (!isset($json_data['data'])) $json_data['data'] = [];
		$json_data['data'] = $new_data;
		return static::log($code, static::ACTION_CREATE, $object, $object_id, $json_data);
	}

	static public function add_tag(int $log_id, string $object='', int $object_id=0): int|bool {
		$param = [
			'log_id' => $log_id,
			'object' => $object,
			'object_id' => $object_id,
			'time' => time(),
			'_mode' => \DB\Common::CSMODE_INSERT,
		];
		return \LogTags::save($param);
	}

	static private function _print_k_v($k, $v) {
		if (!isset($v)) return '';
		if (strpos($k, '_time') !== false || strpos($k, 'Time') !== false) {
			return $v ? date("Y-m-d H:i:s", $v) : $v;
		}
		if (strpos($k, 'password') !== false || strpos($k, '2fa') !== false || strpos($k, 'secret') !== false) {
			return \Config::getInstance()->_hide_password($v);
		}
		return $v ? $v : '';
	}

	static public function format_json_data(array $json_data=[], int $space=0): string {
		$return = '';
		foreach($json_data as $k=>$v) {
			$array = false;
			if (is_array($v)) {
				if (count($v) == 2 && isset($v['old']) && isset($v['new'])) $array = false;
				else $array = true;
			}
			$return .= str_repeat('    ', $space);
			$return .= $k;
			$return .= ': ';
			if ($array) {
				$return .= "{\n";
				$return .= static::format_json_data($v, ++$space);
				$return .= "}\n";
			} else {
				if (is_array($v) && count($v) == 2 && isset($v['old']) && isset($v['new'])) {
					$return .= '<span class="text-secondary">'.static::_print_k_v($k, $v['old']).'</span>';
					$return .= ' => ';
					$return .= '<span class="text-info">'.static::_print_k_v($k, $v['new']).'</span>';
				} elseif (is_array($v)) {
					$return .= '<span class="text-info">'.json_encode($v).'</span>';
				} else {
					$return .= '<span class="text-info">'.static::_print_k_v($k, $v).'</span>';
				}
			}
			$return .= "\n";
		}

		return $return;
	}
}