<?php

class ObjectPermissions extends \DB\MySQLObject{
	static public $table = 'object_permissions';

	const LOGS_OBJECT = 'ObjectPermissions';

	static public function save($param, $table_fields='', $ref_name='id', $add=''): int|bool {
		if (
			isset($param['object']) && $param['object'] == 'user' &&
			isset($param['object_id']) && $param['object_id']
		) {
			\Users::clear_session_cache($param['object_id']);
		}
		return parent::save($param, $table_fields, $ref_name, $add);
	}
}