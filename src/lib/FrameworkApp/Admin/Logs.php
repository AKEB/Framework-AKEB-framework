<?php
namespace FrameworkApp\Admin;

class Logs extends \Routing_Parent implements \Routing_Interface {

	private int $start_time = 0;
	private int $end_time = 0;

	private array $logs = [];
	private array $logs_action_hash = [];
	private array $users = [];
	private array $groups = [];

	private ?array $action = null;
	private ?array $object = null;
	private ?int $object_id=null;



	private bool $can_read_global = false;

	public function Run(?string $start_date='', ?string $end_date='') {
		$this->check_auth();
		$this->check_permissions();

		$this->start_time = 0;
		$this->end_time = 0;
		$start_t = explode('-', (string) $start_date);
		$end_t = explode('-', (string) $end_date);
		if ($start_t && $end_t && count($start_t) == 3 && count($end_t) == 3) {
			$this->start_time = mktime(0,0,0, $start_t[1], $start_t[2], $start_t[0]);
			$this->end_time = mktime(23, 59, 59, $end_t[1], $end_t[2], $end_t[0]);
		}
		if (!$this->start_time || !$this->end_time) {
			common_redirect(sprintf('/admin/logs/%s/%s/',date("Y-m-d", time()), date("Y-m-d", time())));
		}
		$actions = isset($_GET['action']) && $_GET['action'] ? explode(',', $_GET['action']??'') : [];
		$this->action = $actions ? array_map('intval', $actions) : [];
		$objects = isset($_GET['object']) && $_GET['object'] ? explode(',', $_GET['object']??'') : [];
		$this->object = $objects ? array_map('strval', $objects) : [];
		$this->object_id = isset($_GET['object_id']) && intval($_GET['object_id']) ? intval($_GET['object_id']) : null;

		$this->get_data();

		$this->template = new \Template('Logs');
		$this->print_header();
		$this->print_filters();
		$this->print_table();
		$this->print_javascript();
	}

	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);

		$this->can_read_global = \Sessions::checkPermission(\Permissions::LOGS, 0, READ);
		if (!$this->can_read_global) {
			e403();
		}
	}

	private function get_data() {
		if (!$this->start_time || !$this->end_time) {
			return;
		}
		$sql = '';
		$params = [];
		$sql .= sql_pholder(' AND t.`time`>=? AND t.`time`<=?', $this->start_time, $this->end_time);
		if ($this->action) {
			$sql .= sql_pholder(' AND t.`action` IN (?@)', $this->action);
		}
		if ($this->object || $this->object_id) {
			$params['_join'] = 'LEFT JOIN `log_tags` AS lt ON lt.`log_id`=t.`id` OR lt.`log_id` is NULL ';
			if ($this->object && $this->object_id) {
				$sql .= sql_pholder(' AND ((t.`object` IN (?@) AND t.`object_id`=?) OR (lt.`object` IN (?@) AND lt.`object_id`=?))', $this->object, $this->object_id, $this->object, $this->object_id);
			} elseif ($this->object) {
				$sql .= sql_pholder(' AND (t.`object` IN (?@) OR lt.`object` IN (?@))', $this->object, $this->object);
			} elseif ($this->object_id) {
				$sql .= sql_pholder(' AND (t.`object_id`=? OR lt.`object_id`=?)', $this->object_id, $this->object_id);
			}
		}
		$sql .= ' ORDER BY t.`id` ASC';

		$logs = \Logs::data(false, $sql, 't.*', false, false, $params);
		$this->logs_action_hash = \Logs::action_hash();
		$user_ids = [];
		// $group_ids = [];
		$log_ids = [];
		$this->logs = [];
		$this->users = [];
		foreach($logs as $k => $log) {
			$log = [
				'id' => intval($log['id'] ?? 0),
				'user_id' => intval($log['user_id'] ?? 0),
				'original_user_id' => intval($log['original_user_id'] ?? 0),
				'code' => strval(trim($log['code'] ?? '')),
				'action' => $this->logs_action_hash[intval($log['action'] ?? 0)]??'',
				'object' => strval(trim($log['object'] ?? '')),
				'object_id' => intval($log['object_id'] ?? 0),
				'json_data' => json_decode(strval(trim($log['json_data'] ?? '{}')), true),
				'comment' => htmlspecialchars(trim($log['comment'] ?? '')),
				'trace' => str_replace("\n",'<br/>',htmlspecialchars(trim($log['trace'] ?? ''))),
				'time' => isset($log['time']) && $log['time'] > 0 ? date("Y-m-d H:i:s", $log['time']) : '',
			];
			if ($log['object'] == \Users::LOGS_OBJECT) {
				$user_ids[$log['object_id']] = $log['object_id'];
			// } elseif ($log['object'] == \Groups::LOGS_OBJECT) {
			// 	$group_ids[$log['object_id']] = $log['object_id'];
			}
			$log['objects'] = [];

			$this->logs[$log['id']] = $log;
			$log_ids[$log['id']] = $log['id'];
			$user_ids[$log['user_id']] = $log['user_id'];
			$user_ids[$log['original_user_id']] = $log['original_user_id'];

		}
		if ($log_ids) {
			$log_tags = \LogTags::data(['log_id' => array_keys($log_ids)]);
			foreach($log_tags as $log_tag) {
				if (isset($this->logs[$log_tag['log_id']])) {
					if ($log_tag['object'] == \Users::LOGS_OBJECT) {
						$user_ids[$log_tag['object_id']] = $log_tag['object_id'];
					// } elseif ($log_tag['object'] == \Groups::LOGS_OBJECT) {
					// 	$group_ids[$log_tag['object_id']] = $log_tag['object_id'];
					}
					if (!isset($this->logs[$log_tag['log_id']]['objects'][$log_tag['object']])) {
						$this->logs[$log_tag['log_id']]['objects'][$log_tag['object']] = [];
					}
					$this->logs[$log_tag['log_id']]['objects'][$log_tag['object']][$log_tag['object_id']] = true;
				}
			}
		}
		if ($user_ids) {
			$users = \Users::data(['id' => array_keys($user_ids)],'','id, name, surname, email');
			foreach($users as $user) {
				$this->users[$user['id']] = sprintf('%s %s (%s) [ID=%d]', $user['name'], $user['surname'], $user['email'], $user['id']);
			}
		}
		// if ($group_ids) {
		// 	$groups = \Groups::data(['id' => array_keys($group_ids)],'','id, title');
		// 	foreach($groups as $group) {
		// 		$this->groups[$group['id']] = sprintf('%s [ID=%d]', $group['title'], $group['id']);
		// 	}
		// }
	}

	private function print_header() {
		?>
		<div class="float-start"><h2><i class="bi bi-journal-text"></i> <?=\T::Framework_Menu_Logs();?></h2></div>
		<div class="float-end">
			<button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#filtersContainer" aria-expanded="false" aria-controls="filtersContainer">
				<i class="bi bi-funnel"></i> <?=\T::Framework_Common_Filter();?>
			</button>
		</div>
		<div class="clearfix"></div>
		<?php
	}

	private function print_filters() {
		?>
		<div class="collapse" id="filtersContainer">
			<div class="card card-paddings card-body">
				<div class="row">
					<div class="col-md-6 col-lg-5 col-xl-4">
						<label for="dateInput" class="col-form-label"><?=\T::Framework_Logs_Filter_Date();?></label>
						<div class="input-group" id="date" data-td-target-input="nearest" data-td-target-toggle="nearest">
							<input id="dateInput" type="text" class="form-control" data-td-target="#date">
							<span class="input-group-text pointer" data-td-target="#start_date" data-td-toggle="datetimepicker">
								<i class="bi bi-calendar-fill"></i>
							</span>
						</div>
					</div>
					<div class="col">
						<?php
						echo $this->template?->html_select(
							'action', \Logs::action_hash(), $this->action, \T::Framework_Logs_Filter_Action(), false,[
								'with-undefined' => false,
								'data-container' => 'body',
								'vertical' => true,
								'multiple' => true,
								'class1' => '',
								'class2' => '',
							]
						);
						?>
					</div>
					<div class="col">
						<?php
						echo $this->template?->html_select(
							'object', \Logs::object_hash(), $this->object, \T::Framework_Logs_Filter_Object(), false,[
								'with-undefined' => false,
								'data-container' => 'body',
								'vertical' => true,
								'multiple' => true,
								'class1' => '',
								'class2' => '',
							]
						);
						?>
					</div>
					<div class="col">
						<?php
						echo $this->template?->html_input(
							'object_id', $this->object_id??'', \T::Framework_Logs_Filter_ObjectId(), false,[
								'vertical' => true,
								'class1' => '',
								'class2' => '',
							]
						);
						?>
					</div>
				</div>
				<div class="row mt-3">
					<div class="col d-flex flex-row-reverse">
						<button class="btn btn-primary me-3" id="filterButton"><?=\T::Framework_Logs_Filter_Apply();?></button>
						<button class="btn btn-secondary me-3" id="filterButtonReset" type="reset"><?=\T::Framework_Logs_Filter_Reset();?></button>
					</div>
				</div>
			</div>
			<script nonce="<?=\CSP::nonceRandom();?>">
				$(document).ready(function() {
					const picker = new tempusDominus.TempusDominus(document.getElementById('date'), {
						allowInputToggle: true,
						dateRange: true,
						debug: false,
						multipleDatesSeparator: '<?=\T::getCurrentLanguage() == 'ru' ? ' по ' : ' to ';?>',

						display: {
							icons: {
								type: 'icons',
								time: 'bi bi-clock',
								date: 'bi bi-calendar-week',
								up: 'bi bi-arrow-up',
								down: 'bi bi-arrow-down',
								previous: 'bi bi-chevron-left',
								next: 'bi bi-chevron-right',
								today: 'bi bi-calendar-check',
								clear: 'bi bi-trash',
								close: 'bi bi-x'
							},
							keepOpen: false,
							buttons: {
								today: true,
								clear: true,
								close: true
							},
							viewMode: 'calendar',
							inline: false,
							theme: 'auto',
							components: {
								calendar: true,
								date: true,
								month: true,
								year: true,
								decades: true,
								clock: false,
								hours: false,
								minutes: false,
								seconds: false,
							},
						},
						localization: {
							today: '<?=\T::Framework_DateTimePicker_today();?>',
							clear: '<?=\T::Framework_DateTimePicker_clear();?>',
							close: '<?=\T::Framework_DateTimePicker_close();?>',
							selectMonth: '<?=\T::Framework_DateTimePicker_selectMonth();?>',
							previousMonth: '<?=\T::Framework_DateTimePicker_previousMonth();?>',
							nextMonth: '<?=\T::Framework_DateTimePicker_nextMonth();?>',
							selectYear: '<?=\T::Framework_DateTimePicker_selectYear();?>',
							previousYear: '<?=\T::Framework_DateTimePicker_previousYear();?>',
							nextYear: '<?=\T::Framework_DateTimePicker_nextYear();?>',
							selectDecade: '<?=\T::Framework_DateTimePicker_selectDecade();?>',
							previousDecade: '<?=\T::Framework_DateTimePicker_previousDecade();?>',
							nextDecade: '<?=\T::Framework_DateTimePicker_nextDecade();?>',
							previousCentury: '<?=\T::Framework_DateTimePicker_previousCentury();?>',
							nextCentury: '<?=\T::Framework_DateTimePicker_nextCentury();?>',
							pickHour: '<?=\T::Framework_DateTimePicker_pickHour();?>',
							incrementHour: '<?=\T::Framework_DateTimePicker_incrementHour();?>',
							decrementHour: '<?=\T::Framework_DateTimePicker_decrementHour();?>',
							pickMinute: '<?=\T::Framework_DateTimePicker_pickMinute();?>',
							incrementMinute: '<?=\T::Framework_DateTimePicker_incrementMinute();?>',
							decrementMinute: '<?=\T::Framework_DateTimePicker_decrementMinute();?>',
							pickSecond: '<?=\T::Framework_DateTimePicker_pickSecond();?>',
							incrementSecond: '<?=\T::Framework_DateTimePicker_incrementSecond();?>',
							decrementSecond: '<?=\T::Framework_DateTimePicker_decrementSecond();?>',
							toggleMeridiem: '<?=\T::Framework_DateTimePicker_toggleMeridiem();?>',
							selectTime: '<?=\T::Framework_DateTimePicker_selectTime();?>',
							selectDate: '<?=\T::Framework_DateTimePicker_selectDate();?>',
							locale: '<?=\T::getCurrentLanguage();?>',
							startOfTheWeek: 1,
							dateFormats: {
								LL: 'dd MMMM yyyy',
							},
							format: 'LL'
						},
					});
					picker.dates.clear();
					const startDate = picker.dates.parseInput(new Date('<?=date("Y-m-d",$this->start_time);?>'));
					picker.dates.setValue(startDate, 0);
					<?php if (date("Y-m-d",$this->start_time) != date("Y-m-d",$this->end_time)) { ?>
						const endDate = picker.dates.parseInput(new Date('<?=date("Y-m-d",$this->end_time);?>'));
						picker.dates.setValue(endDate, 1);
					<?php } ?>

					$('#filterButton').on('click', function() {
						const start_js_date = picker.dates.picked[0] ? picker.dates.picked[0] : new Date();
						const end_js_date = picker.dates.picked[1] ? picker.dates.picked[1] : start_js_date;
						const start_date = formatDate(start_js_date);
						const end_date = formatDate(end_js_date);

						const _action = $('#action').val();
						const _object = $('#object').val();
						const _object_id = $('#object_id').val();

						let params = [];
						if (_action){
							params.push('action=' + _action);
						}
						if (_object){
							params.push('object=' + _object);
						}
						if (_object_id){
							params.push('object_id=' + _object_id);
						}
						const new_url = '/admin/logs/' + start_date + '/' + end_date +'/' + (params.length > 0 ? '?' + params.join('&') : '');
						// console.log(new_url);
						window.location.href = new_url;
						return false;
					});
					$('#filterButtonReset').on('click', function() {
						picker.dates.clear();
						const startDate = picker.dates.parseInput(new Date('<?=date("Y-m-d",$this->start_time);?>'));
						picker.dates.setValue(startDate, 0);
						<?php if (date("Y-m-d",$this->start_time) != date("Y-m-d",$this->end_time)) { ?>
							const endDate = picker.dates.parseInput(new Date('<?=date("Y-m-d",$this->end_time);?>'));
							picker.dates.setValue(endDate, 1);
						<?php } ?>
						$('#action').selectpicker('val', ['<?=implode("','",$this->action);?>']).change();
						$('#object').selectpicker('val', ['<?=implode("', '",$this->object);?>']).change();
						$('#object_id').val('<?=intval($this->object_id)?>').change();
					});
				});
			</script>
		</div>
		<?php
	}

	private function print_table() {
		?>
		<div class="row d-flex justify-content-center">
			<table class="table table-transparent table-responsive" data-order='[[ 0, "desc" ]]' id="LogsTable">
				<thead class="">
					<tr>
						<th scope="col" class="align-middle" data-priority="1">ID</th>
						<th scope="col" class="align-middle" data-priority="2"><?=\T::Framework_Logs_Report_User();?></th>
						<th scope="col" class="align-middle" data-priority="3"><?=\T::Framework_Logs_Report_Code();?></th>
						<th scope="col" class="align-middle" data-priority="6"><?=\T::Framework_Logs_Report_Action();?></th>
						<th scope="col" class="align-middle" data-priority="4"><?=\T::Framework_Logs_Report_Object();?></th>
						<th scope="col" class="align-middle" data-priority="7"><?=\T::Framework_Logs_Report_Data();?></th>
						<th scope="col" class="align-middle" data-priority="5"><?=\T::Framework_Logs_Report_Time();?></th>
						<!-- <th scope="col" class="align-middle" data-priority="9"><?=\T::Framework_Logs_Report_Trace();?></th> -->
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->logs as $log) {
						$objects = [];
						foreach($log['objects'] as $object=>$v) {
							$object_title = $object;
							if (isset(\Logs::object_hash()[$object_title])) {
								$object_title = \Logs::object_hash()[$object_title];
							}
							if ($object == \Users::LOGS_OBJECT) {
								foreach($v as $id=>$_) {
									$objects[] = '<span class="badge text-bg-info">'.($this->users[$id] ?? ($object_title.' [ID='.$id.']')).'</span>';
								}
							} elseif ($object == \Groups::LOGS_OBJECT) {
								foreach($v as $id=>$_) {
									$objects[] = '<span class="badge text-bg-warning">'.$object_title.' [ID='.$id.']'.'</span>';
								}
							} else {
								foreach($v as $id=>$_) {
									$objects[] = '<span class="badge text-bg-secondary">'.$object_title.' [ID='.$id.']'.'</span>';
								}
							}
						}

						if (isset(\Logs::object_hash()[$log['object']])) {
							$log['object'] = \Logs::object_hash()[$log['object']];
						}
						$object = $log['object'].' [ID='.$log['object_id'].']';
						if ($log['object'] == \Users::LOGS_OBJECT) {
							$object = $this->users[$log['object_id']] ?? $object;
						} elseif ($log['object'] == \Groups::LOGS_OBJECT) {
							$object = $this->groups[$log['object_id']] ?? $object;
						}

						$object_title = \Users::LOGS_OBJECT;
						if (isset(\Logs::object_hash()[\Users::LOGS_OBJECT])) {
							$object_title = \Logs::object_hash()[\Users::LOGS_OBJECT];
						}

						$log['original_user'] = '';
						$log['user'] = '';
						if ($log['original_user_id']) {
							$log['original_user'] = $this->users[$log['original_user_id']] ?? $object_title.' [ID='.$log['original_user_id'].']';
						}
						if ($log['user_id']) {
							$log['user'] = $this->users[$log['user_id']] ?? $object_title.' [ID='.$log['user_id'].']';
						}
						if ($log['original_user_id'] != $log['user_id']) {
							$log['original_user'] = sprintf('%s <span class="badge text-bg-info">%s</span>', $log['original_user'], $log['user']);
						}

						$data_text = \Logs::format_json_data($log['json_data']);

						?>
						<tr>
							<th scope="row" class="align-middle"><?=$log['id'];?></th>
							<td class="align-middle"><?=$log['original_user'];?></td>
							<td class="align-middle"><?=$log['code'];?></td>
							<td class="align-middle"><?=$log['action'];?></td>
							<td class="align-middle"><?=$object;?><br/>
								<?=implode(' ', $objects);?>
							</td>
							<td class="align-middle">
								<?=$log['comment'];?><br/>
								<pre><?=rtrim($data_text);?></pre>
							</td>
							<td class="align-middle"><?=$log['time'];?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function print_javascript() {
		?>
		<script nonce="<?=\CSP::nonceRandom();?>">
			$(document).ready(function(){
				new DataTable('#LogsTable');


			});
		</script>
		<?php
	}

}
