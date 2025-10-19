<?php

class UserGroups extends \DB\MySQLObject{
	static public $table = 'user_groups';

	const LOGS_OBJECT = 'UserGroups';

	static public function save($param, $table_fields='', $ref_name='id', $add=''): int|bool {
		if ($param['user_id']??false) {
			\Users::clear_session_cache($param['user_id']);
		}
		return parent::save($param, $table_fields, $ref_name, $add);
	}

}