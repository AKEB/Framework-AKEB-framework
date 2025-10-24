<?php

class Template {

	static public string $head_additional = '';
	static private string $project_name = 'AKEB Framework';
	static private string $theme = 'dark';
	static private \MenuItems $menu_items;
	static private \MenuItems $menu_admin_items;

	static private array $css_files = [];
	static private array $js_files = [];

	static private array $head_metas = [];
	static private array $head_links = [];


	/**
	 * setProjectName
	 *
	 * @param  string $name Set Project title
	 * @return void
	 */
	public static function setProjectName(string $name) {
		static::$project_name = $name;
	}
	public static function getProjectName(): string {
		return static::$project_name;
	}

	/**
	 * setTheme
	 *
	 * @param  string $theme dark, light or auto
	 * @return void
	 */
	public static function setTheme(string $theme): void {
		static::$theme = $theme;
	}
	public static function getTheme(): string {
		return static::$theme;
	}

	public static function setMenuItems(\MenuItems $menu_items) {
		static::$menu_items = $menu_items;
	}

	public static function addMenuItem(\MenuItem $menu_item) {
		if (!isset(static::$menu_items)) {
			static::$menu_items = new \MenuItems();
		}
		static::$menu_items->add($menu_item);
	}

	public static function setMenuAdminItems(\MenuItems $menu_admin_items) {
		static::$menu_admin_items = $menu_admin_items;
	}

	public static function addMenuAdminItem(\MenuItem $menu_admin_item) {
		if (!isset(static::$menu_admin_items)) {
			static::$menu_admin_items = new \MenuItems();
		}
		static::$menu_admin_items->add($menu_admin_item);
	}

	public static function addCSSFile(string $css_file) {
		static::$css_files[] = $css_file;
	}
	public static function setCSSFiles(array $css_files) {
		static::$css_files = $css_files;
	}
	public static function getCSSFiles(): array {
		return static::$css_files;
	}

	public static function addJSFile(string $js_file) {
		static::$js_files[] = $js_file;
	}
	public static function setJSFiles(array $js_files) {
		static::$js_files = $js_files;
	}
	public static function getJSFiles(): array {
		return static::$js_files;
	}

	public static function addHeadMeta(array $head_meta) {
		static::$head_metas[] = $head_meta;
	}
	public static function setHeadMetas(array $head_metas) {
		static::$head_metas = $head_metas;
	}
	public static function getHeadMetas(): array {
		return static::$head_metas;
	}

	public static function addHeadLink(array $head_link) {
		static::$head_links[] = $head_link;
	}
	public static function setHeadLinks(array $head_links) {
		static::$head_links = $head_links;
	}
	public static function getHeadLinks(): array {
		return static::$head_links;
	}


	public function __construct(bool $withHeader=true) {
		$lang = \T::getCurrentLanguage();

		$css_files = [];
		$js_files = [];

		if (\Config::getInstance()->development) {
			$css_files = [
				'/vendor/akeb/framework/src/css/bootstrap-icons.min.css',
				'/vendor/akeb/framework/src/css/bootstrap.min.css',
				'/vendor/akeb/framework/src/css/datatables.min.css',
				'/vendor/akeb/framework/src/css/bootstrap-select.min.css',
				'/vendor/akeb/framework/src/css/tempus-dominus.min.css',
				'/vendor/akeb/framework/src/css/main.css',
			];
			$js_files = [
				'/vendor/akeb/framework/src/js/popper.min.js',
				'/vendor/akeb/framework/src/js/bootstrap.bundle.min.js',
				'/vendor/akeb/framework/src/js/datatables.min.js',
				'/vendor/akeb/framework/src/js/bootstrap-select.min.js',
				'/vendor/akeb/framework/src/js/bootstrap-select-'.\T::getCurrentLanguage().'.min.js',
				'/vendor/akeb/framework/src/js/tempus-dominus.min.js',
				'/vendor/akeb/framework/src/js/locale_'.\T::getCurrentLanguage().'.js',
				'/vendor/akeb/framework/src/js/main.js',
			];
		} else {
			$css_files = [
				'/css/framework/bootstrap-icons.min.css',
				'/css/framework/bootstrap.min.css',
				'/css/framework/datatables.min.css',
				'/css/framework/bootstrap-select.min.css',
				'/css/framework/tempus-dominus.min.css',
				'/css/framework/main.css',
			];
			$js_files = [
				'/js/framework/popper.min.js',
				'/js/framework/bootstrap.bundle.min.js',
				'/js/framework/datatables.min.js',
				'/js/framework/bootstrap-select.min.js',
				'/js/framework/bootstrap-select-'.\T::getCurrentLanguage().'.min.js',
				'/js/framework/tempus-dominus.min.js',
				'/js/framework/locale_'.\T::getCurrentLanguage().'.js',
				'/js/framework/main.js',
			];
		}

		$css_files = array_merge($css_files, static::getCSSFiles());

		$js_files = array_merge($js_files, static::getJSFiles());

		$head_metas = array_merge([
			['charset' => 'UTF-8'],
			['name' => 'viewport','content' => 'width=device-width, initial-scale=1'],
			['http-equiv' => 'X-UA-Compatible','content' => 'IE=edge'],
			['name' => 'apple-mobile-web-app-title','content' => static::getProjectName()],
			['name' => 'msapplication-TileImage','content' => "/images/ms-icon-144x144.png"]
		], static::getHeadMetas());

		$head_links = array_merge([
			['rel' => 'shortcut icon', 'href' => '/images/favicon.ico', 'type' => 'image/x-icon'],
			['rel' => 'apple-touch-icon', 'sizes' => '57x57', 'href' => '/images/apple-icon-57x57.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '60x60', 'href' => '/images/apple-icon-60x60.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '72x72', 'href' => '/images/apple-icon-72x72.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '76x76', 'href' => '/images/apple-icon-76x76.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '114x114', 'href' => '/images/apple-icon-114x114.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '120x120', 'href' => '/images/apple-icon-120x120.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '144x144', 'href' => '/images/apple-icon-144x144.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '152x152', 'href' => '/images/apple-icon-152x152.png'],
			['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/images/apple-icon-180x180.png'],
			['rel' => 'icon', 'type' => 'image/png', 'sizes' => '192x192', 'href' => '/images/android-icon-192x192.png'],
			['rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => '/images/favicon-32x32.png'],
			['rel' => 'icon', 'type' => 'image/png', 'sizes' => '96x96', 'href' => '/images/favicon-96x96.png'],
			['rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => '/images/favicon-16x16.png'],
			['rel' => 'manifest', 'href' => '/manifest.json'],
		], static::getHeadLinks());

		?>
		<!doctype html>
		<html lang="<?=$lang;?>" data-bs-theme="<?=static::getTheme();?>" class="h-100">
			<head>
				<title><?=static::getProjectName();?></title>
				<?php
				foreach($head_metas as $head_meta) {
					echo "<meta ";
					foreach($head_meta as $key=>$value) {
						echo $key.'="'.addslashes($value).'"';
					}
					echo "/>\n";
				}
				foreach($head_links as $head_link) {
					echo "<link ";
					foreach($head_link as $key=>$value) {
						echo $key.'="'.addslashes($value).'"';
					}
					echo "/>\n";
				}
				foreach($css_files as $css_file) {
					?>
					<link href="<?=file_anticache($css_file);?>" rel="stylesheet" nonce="<?=\CSP::nonceRandom();?>"/>
					<?php
				}
				foreach($js_files as $js_file) {
					?>
					<script src="<?=file_anticache($js_file);?>" nonce="<?=\CSP::nonceRandom();?>"></script>
					<?php
				}
				?>
				<script nonce="<?=\CSP::nonceRandom();?>">
					setStoredTheme('<?=static::getTheme();?>');
					<?php if ($withHeader) { ?>
						let wss = new WSS('<?=\Sessions::get_session_id();?>');
					<?php } ?>
				</script>
			</head>
			<body class="d-flex flex-column min-vh-100 min-hw-100 gradient-custom">
				<div aria-live="polite" aria-atomic="true" class="position-static">
					<div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container">
					</div>
				</div>
				<div class="dropdown position-fixed top-0 end-0 mt-3 me-3 bd-mode-toggle languageToggle">
					<button class="btn btn-dark py-2 dropdown-toggle d-flex align-items-center" id="bd-theme" type="button" aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme (dark)">
						<i class="bi bi-globe"></i>
						<span class="visually-hidden" id="bd-theme-text">Toggle language</span>
					</button>
					<ul class="dropdown-menu shadow languageToggle">
						<li><a class="dropdown-item <?=$lang == 'en' ? 'text-bg-secondary' : '';?>" href="<?=common_change_query('lang','en');?>">English</a></li>
						<li><a class="dropdown-item <?=$lang == 'ru' ? 'text-bg-secondary' : '';?>" href="<?=common_change_query('lang','ru');?>">Русский</a></li>
					</ul>
				</div>
				<?php
					if ($withHeader) {
						$this->header();
					}
					?>
					<main>
						<div class="container-xxl">
		<?php
	}

	public function header() {
		$current_path = $_SERVER['DOCUMENT_URI'];
		$current_path = str_replace('index.php', '', $current_path);
		if (!isset(static::$menu_items)) {
			static::$menu_items = new \MenuItems();
		}
		if (!isset(static::$menu_admin_items)) {
			static::$menu_admin_items = new \MenuItems();
		}
		$admin_menu = new \MenuItem('bi bi-lock', \T::Framework_Menu_Admin(), '/admin/', static::$menu_admin_items,
			new \MenuPermissionItems(new \MenuPermissionItem(\Permissions::ADMIN, 0, READ))
		);
		static::$menu_items->add($admin_menu);

		$menu_items = [];
		foreach (static::$menu_items as $k => $item) {
			if (!($item instanceof \MenuItem)) {
				continue;
			}
			if ($item->hasPermissions()) {
				$access = false;
				foreach ($item->getPermissions() as $permission) {
					if (!($permission instanceof \MenuPermissionItem)) continue;
					if (\Sessions::checkPermission(
						$permission->getPermission(),
						$permission->getSubjectId(),
						$permission->getAccessType()
					)) {
						$access = true;
						break;
					}
				}
				if (!$access) {
					continue;
				}
			}
			$active = false;
			$children = [];
			if ($item->hasChildren()) {
				foreach ($item->getChildren() as $k2 => $item2) {
					if (!($item2 instanceof \MenuItem)) {
						continue;
					}
					if ($item2->hasPermissions()) {
						$access = false;
						foreach ($item2->getPermissions() as $permission) {
							if (!($permission instanceof \MenuPermissionItem)) continue;
							if (\Sessions::checkPermission(
								$permission->getPermission(),
								$permission->getSubjectId(),
								$permission->getAccessType()
							)) {
								$access = true;
								break;
							}
						}
						if (!$access) {

							continue;
						}
					}
					$active2 = false;
					if ($item2->getLink() == $current_path) {
						$active2 = true;
					}
					$children[] = [
						'icon' => $item2->getIcon(),
						'title' => $item2->getTitle(),
						'link'=> $item2->getLink(),
						'class' => $active2 ? 'active' : '',
					];
				}
				if (!$children) continue;
			} else {
				if ($item->getLink() == $current_path) {
					$active = true;
				}
			}
			$menu_items[] = [
				'icon' => $item->getIcon(),
				'title' => $item->getTitle(),
				'link'=> $item->getLink(),
				'class' => $active ? 'active' : '',
				'children' => $children ? $children : null,
			];
		}
		$currentUser = \Sessions::currentUser();
		if (!$currentUser || !$currentUser['id']) {
			common_redirect('/login/');
			return;
		}
		$userAvatar = "https://gravatar.com/avatar/".hash('sha256', strtolower(trim($currentUser['email'])));
		?>
		<header class="p-3 mb-3 ">
			<div class="container-xxl pb-3 border-bottom">
				<div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start">
					<a href="/" class="d-flex align-items-center mb-2 mb-md-0 link-body-emphasis text-decoration-none">
						<img src="/images/android-icon-48x48.png" alt="<?=static::getProjectName();?>" width="48" height="48" class="rounded-circle">
					</a>
					<ul class="nav nav-underline col-12 col-md-auto me-md-auto mb-2 justify-content-center mb-md-0">
						<?php
						foreach ($menu_items as $item) {
							if (isset($item['children'])) {
								?>
								<li class="nav-item dropdown">
									<a class="nav-link <?=$item['class'];?> dropdown-toggle" data-bs-toggle="dropdown" href="<?=$item['link'];?>" role="button" aria-expanded="false"><?=isset($item['icon']) && $item['icon'] ? '<i class="'.$item['icon'].'"></i> ':'';?><?=$item['title'];?></a>
									<ul class="dropdown-menu">
										<?php
										foreach($item['children'] as $item2) {
											?>
											<li><a class="dropdown-item <?=$item2['class'];?>" href="<?=$item2['link'];?>"><?=isset($item2['icon']) && $item2['icon'] ? '<i class="'.$item2['icon'].'"></i> ':'';?><?=$item2['title'];?></a></li>
											<?php
										}
										?>
									</ul>
								</li>
								<?php
							} else {
								?>
								<li class="nav-item">
									<a href="<?=$item['link'];?>" class="nav-link px-2 <?=$item['class'];?>"><?=isset($item['icon']) && $item['icon'] ? '<i class="'.$item['icon'].'"></i> ':'';?><?=$item['title'];?></a>
								</li>
								<?php
							}
						}
						?>
					</ul>
					<?php
					if (\Config::getInstance()->app_debug) {
						?>
						<div>
						<div class="d-inline me-4 fs-6 text-warning-emphasis">
							<span class="d-inline d-sm-none">XS</span>
							<span class="d-none d-sm-inline d-md-none">SM</span>
							<span class="d-none d-md-inline d-lg-none">MD</span>
							<span class="d-none d-lg-inline d-xl-none">LG</span>
							<span class="d-none d-xl-inline d-xxl-none">XL</span>
							<span class="d-none d-xxl-inline">XXL</span>
						</div>
						</div>
						<?php
					}
					?>
					<button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#notificationCanvas" aria-controls="notificationCanvas">
						<i id="notificationButton" class="bi bi-bell h4"></i>
					</button>
					<div class="dropdown text-md-end me-4">
						<a href="#" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
							<img src="<?=$userAvatar;?>" alt="avatar" width="32" height="32" class="rounded-circle"> <?=$currentUser['name'];?>
						</a>
						<ul class="dropdown-menu text-small">
							<li><a class="dropdown-item" href="/settings/"><i class="bi bi-gear"></i> <?=\T::Framework_Profile_Settings();?></a></li>
							<li><hr class="dropdown-divider"></li>
							<li><a class="dropdown-item" href="/logout/"><i class="bi bi-box-arrow-right"></i> <?=\T::Framework_SignOut();?></a></li>
						</ul>
					</div>
				</div>
				<div class="offcanvas offcanvas-end" tabindex="-1" id="notificationCanvas" aria-labelledby="notificationCanvasLabel">
					<div class="offcanvas-header">
						<h5 class="offcanvas-title" id="notificationCanvasLabel">
							<i class="bi bi-arrow-left-circle text-info pointer" data-bs-dismiss="offcanvas" aria-label="Close"></i>
							<?=\T::Framework_Notifications_Title();?>
						</h5>
					</div>
					<div class="offcanvas-body" id="notificationBody"></div>
				</div>
			</div>
		</header>
		<?php
	}

	public function pagination(int $page, int $total): void {
		$per_page = $_COOKIE['per_page'];
		$max_page = max(1,ceil($total / $per_page));
		$pages = [];
		$pages[1] = true;
		$pages[$max_page] = true;
		if ($max_page > 7) {
			for($i=min(max(1, $page-1), max(1, $max_page-4)); $i<= max(5, min($max_page, $page+1)); $i++) {
				$pages[$i] = true;
			}
		} else {
			for($i=1; $i<=$max_page; $i++) {
				$pages[$i] = true;
			}
		}
		ksort($pages);
		?>

		<nav aria-label="Search results pages">
			<ul class="pagination justify-content-end">
				<li class="page-item">
					<button class="page-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><?=$per_page;?></button>
					<ul class="dropdown-menu">
						<?php
						foreach(\Config::getInstance()->per_page_counts as $count) {
							?>
							<li><a class="dropdown-item" href="<?=common_change_query('per_page', $count);?>"><?=$count;?></a></li>
							<?php
						}
						?>
					</ul>
				</li>
				<li class="page-item <?=$page < 2 ? 'disabled' : '';?>">
					<a class="page-link" <?=$page > 1 ? 'href="'.common_change_query('page', ($page-1)).'"':'';?>>&laquo;</a>
				</li>

				<?php
				$pref_page = 0;

				foreach($pages as $i=>$show) {
					if (!$show) continue;
					if ($i != $pref_page+1) {
						if ($i != $pref_page+2) {
							?>
							<li class="page-item disabled"><a class="page-link">..</a></li>
							<?php
						} else {
							?>
							<li class="page-item"><a class="page-link <?=($i-1)==$page ? 'active':'';?>" href="<?=common_change_query('page', $i-1);?>"><?=$i-1;?></a></li>
							<?php
						}
					}
					?>
					<li class="page-item"><a class="page-link <?=$i==$page ? 'active':'';?>" href="<?=common_change_query('page', $i);?>"><?=$i;?></a></li>
					<?php
					$pref_page = $i;
				}
				?>
				<li class="page-item <?=$page >= $max_page ? 'disabled' : '';?>">
					<a class="page-link" <?=$page < $max_page ? 'href="'.common_change_query('page', ($page+1)).'"':'';?>>&raquo;</a>
				</li>
			</ul>
		</nav>
		<?php
	}

	public function html_switch(string $name, int $value, string $title, bool $required=false, $params=[]): string {
		if (!isset($params['id'])) $params['id'] = $name;
		if (!isset($params['class1'])) $params['class1'] = 'col-xs-12 col-sm-12 col-md-3';
		if (!isset($params['class2'])) $params['class2'] = 'col-xs-12 col-sm-12 col-md-9';
		if (!isset($params['valid-feedback'])) $params['valid-feedback'] = '';
		if (!isset($params['invalid-feedback'])) $params['invalid-feedback'] = '';

		if ($required) {
			if (!$params['invalid-feedback']) $params['invalid-feedback'] = \T::Framework_Common_FormRequired();
			// if (!$params['valid-feedback']) $params['valid-feedback'] = \T::Framework_Common_FormLooksGood();
		}
		$html = '';
		$html .= '<div class="mb-3 row">';
		$html .= '	<label for="'.$params['id'].'" class="'.$params['class1'].' col-form-label">'.$title.($required ? ' <sup>*</sup>' : '').'</label>';
		$html .= '	<div class="'.$params['class2'].'">';
		$html .= '<div class="mt-2 form-check form-switch">';
		$html .= '	<input class="form-check-input" name="'.$name.'" value="'.$value.'" type="checkbox" role="switch" '.($required ? 'required' : '').' id="'.$params['id'].'" switch '.($value ? 'checked' : '').'>';
		if ($params['valid-feedback']) {
				$html .= '		<div class="valid-feedback">'.$params['valid-feedback'].'</div>';
			}
			if ($params['invalid-feedback']) {
				$html .= '		<div class="invalid-feedback">'.$params['invalid-feedback'].'</div>';
			}
			$html .= '</div>';
		$html .= '	</div>';
		$html .= '</div>';

		return $html;

	}

	public function html_flags(string $name, array $flags_hash, int $value, string $title, bool $required=false, $params=[]): string {
		if (!isset($params['id'])) $params['id'] = $name;
		if (!isset($params['class1'])) $params['class1'] = 'col-xs-12 col-sm-12 col-md-3';
		if (!isset($params['class2'])) $params['class2'] = 'col-xs-12 col-sm-12 col-md-9';
		if (!isset($params['valid-feedback'])) $params['valid-feedback'] = '';
		if (!isset($params['invalid-feedback'])) $params['invalid-feedback'] = '';

		if ($required) {
			if (!$params['invalid-feedback']) $params['invalid-feedback'] = \T::Framework_Common_FormRequired();
			// if (!$params['valid-feedback']) $params['valid-feedback'] = \T::Framework_Common_FormLooksGood();
		}
		$html = '';

		$html .= '<div class="mb-3 row">';
		$html .= '	<label class="'.$params['class1'].' col-form-label">'.$title.($required ? ' <sup>*</sup>' : '').'</label>';
		$html .= '	<div class="mt-2 '.$params['class2'].'">';
		foreach($flags_hash as $k=>$v) {
			$id = str_replace('[]','['.$k.']', $name);
			$html .= '<div class="form-check form-switch">';
			$html .= '	<input class="form-check-input" name="'.$name.'" value="'.$k.'" type="checkbox" role="switch" '.($required ? 'required' : '').' id="'.$id.'" switch '.($value & $k ? 'checked' : '').'>';
			$html .= '	<label class="form-check-label" for="'.$id.'">'.$v.'</label>';
			if ($params['valid-feedback']) {
				$html .= '		<div class="valid-feedback">'.$params['valid-feedback'].'</div>';
			}
			if ($params['invalid-feedback']) {
				$html .= '		<div class="invalid-feedback">'.$params['invalid-feedback'].'</div>';
			}
			$html .= '</div>';
		}
		$html .= '	</div>';
		$html .= '</div>';

		return $html;
	}

	public function html_input(string $name, string $value, string $title, bool $required=false, array $params=[]): string {
		if (!isset($params['id'])) $params['id'] = $name;
		if (!isset($params['class1'])) $params['class1'] = 'col-xs-12 col-sm-12 col-md-3';
		if (!isset($params['class2'])) $params['class2'] = 'col-xs-12 col-sm-12 col-md-9';
		if (!isset($params['valid-feedback'])) $params['valid-feedback'] = '';
		if (!isset($params['invalid-feedback'])) $params['invalid-feedback'] = '';
		if (!isset($params['type'])) $params['type'] = 'text';
		if (!isset($params['maxlength'])) $params['maxlength'] = '';
		if (!isset($params['minlength'])) $params['minlength'] = '';
		if (!isset($params['max'])) $params['max'] = '';
		if (!isset($params['min'])) $params['min'] = '';
		if (!isset($params['step'])) $params['step'] = '';
		if (!isset($params['placeholder'])) $params['placeholder'] = '';
		if (!isset($params['password-alert'])) $params['password-alert'] = false;
		if (!isset($params['readonly'])) $params['readonly'] = false;
		if (!isset($params['add_after'])) $params['add_after'] = '';
		if (!isset($params['add_before'])) $params['add_before'] = '';
		if (!isset($params['rows'])) $params['rows'] = '';
		if (!isset($params['cols'])) $params['cols'] = '';

		if ($required) {
			if (!$params['invalid-feedback']) $params['invalid-feedback'] = \T::Framework_Common_FormRequired();
			// if (!$params['valid-feedback']) $params['valid-feedback'] = \T::Framework_Common_FormLooksGood();
		}

		$html = '';
		$html .= '<div class="mb-3 row">';
		$html .= '	<label for="'.$params['id'].'" class="'.$params['class1'].' col-form-label">'.$title.($required ? ' <sup>*</sup>' : '').'</label>';
		$html .= '	<div class="'.$params['class2'].'">';
		if ($params['type'] == 'password') {
			$button = '';
			$button .= '<button class="btn btn-secondary togglePassword" data-input-id="'.$name.'" type="button" tabindex="-1">';
			$button .= '<i class="bi bi-eye" id="'.$name.'-icon"></i>';
			$button .= '</button>';

			$params['add_after'] = $button. $params['add_after'];
		}

		if ($params['add_before'] || $params['add_after']) {
			$html .= '<div class="input-group '.($params['valid-feedback'] || $params['invalid-feedback'] ? 'has-validation':'').'">';
		}
		if ($params['add_before']) {
			$html .= $params['add_before'];
		}
		if ($params['type'] == 'textarea') {
			$html .= '<textarea
				class="'.($params['readonly'] ? 'form-control-plaintext':'form-control').'"
				id="'.$params['id'].'"
				name="'.$name.'"
				placeholder="'.$params['placeholder'].'"'.
				($required ? ' required' : '').'
				autocomplete="off"'.
				($params['rows']? ' rows="'.$params['rows'].'"': '').
				($params['cols']? ' cols="'.$params['cols'].'"': '').
				($params['maxlength']? ' maxlength="'.$params['maxlength'].'"': '').
				($params['minlength']? ' minlength="'.$params['minlength'].'"': '').
				($params['max']? ' max="'.$params['max'].'"': '').
				($params['min']? ' min="'.$params['min'].'"': '').
				($params['step']? ' step="'.$params['step'].'"': '').
				($params['readonly'] ? ' readonly' : '').'
			>';
			$html .= $value;
			$html .= '</textarea>';
		} else {
			$html .= '<input
				type="'.$params['type'].'"
				class="'.($params['readonly'] ? 'form-control-plaintext':'form-control').'"
				id="'.$params['id'].'"
				name="'.$name.'"
				placeholder="'.$params['placeholder'].'"
				value="'.$value.'"'.
				($required ? ' required' : '').'
				autocomplete="off"'.
				($params['maxlength']? ' maxlength="'.$params['maxlength'].'"': '').
				($params['minlength']? ' minlength="'.$params['minlength'].'"': '').
				($params['max']? ' max="'.$params['max'].'"': '').
				($params['min']? ' min="'.$params['min'].'"': '').
				($params['step']? ' step="'.$params['step'].'"': '').
				($params['type'] == 'number' ? ' pattern="\d*"':'').
				($params['readonly'] ? ' readonly' : '').'
			>';
		}
		if ($params['add_after']) {
			$html .= $params['add_after'];
		}
		if ($params['valid-feedback']) {
			$html .= '<div class="valid-feedback">'.$params['valid-feedback'].'</div>';
		}
		if ($params['invalid-feedback']) {
			$html .= '<div class="invalid-feedback">'.$params['invalid-feedback'].'</div>';
		}
		if ($params['add_before'] || $params['add_after']) {
			$html .= '</div>';
		}

		if ($params['type'] == 'password' && $params['password-alert']) {
			$html .= '<div class="alert px-4 py-3 mb-0 d-none" role="alert" id="password-alert">';
			$html .= '	<ul class="list-unstyled mb-0 alert alert-warning">';
			$html .= '		<li class="requirements leng">';
			$html .= '			<i class="bi bi-check-lg text-success me-3"></i>';
			$html .= '			<i class="bi bi-x-lg text-danger me-3"></i>';
			$html .= '			Your password must have at least 8 chars</li>';
			$html .= '		<li class="requirements big-letter">';
			$html .= '			<i class="bi bi-check-lg text-success me-3"></i>';
			$html .= '			<i class="bi bi-x-lg text-danger me-3"></i>';
			$html .= '			Your password must have at least 1 big letter.</li>';
			$html .= '		<li class="requirements small-letter">';
			$html .= '			<i class="bi bi-check-lg text-success me-3"></i>';
			$html .= '			<i class="bi bi-x-lg text-danger me-3"></i>';
			$html .= '			Your password must have at least 1 small letter.</li>';
			$html .= '		<li class="requirements num">';
			$html .= '			<i class="bi bi-check-lg text-success me-3"></i>';
			$html .= '			<i class="bi bi-x-lg text-danger me-3"></i>';
			$html .= '			Your password must have at least 1 number.</li>';
			$html .= '		<li class="requirements special-char">';
			$html .= '			<i class="bi bi-check-lg text-success me-3"></i>';
			$html .= '			<i class="bi bi-x-lg text-danger me-3"></i>';
			$html .= '			Your password must have at least 1 special char.</li>';
			$html .= '	</ul>';
			$html .= '</div>';
			$html .= '<script nonce="'.\CSP::nonceRandom().'">validatePasswordAlert(\''.$params['id'].'\');</script>';
		}
		$html .= '	</div>';
		$html .= '</div>';
		return $html;

	}

	public function html_select(string $name, array $data_hash, string|int|array $values, string $title, bool $required, $params=[]): string {
		if (!isset($params['id'])) $params['id'] = $name;
		if (!isset($params['class1'])) $params['class1'] = 'col-xs-12 col-sm-12 col-md-3';
		if (!isset($params['class2'])) $params['class2'] = 'col-xs-12 col-sm-12 col-md-9';
		if (!isset($params['valid-feedback'])) $params['valid-feedback'] = '';
		if (!isset($params['invalid-feedback'])) $params['invalid-feedback'] = '';
		if (!isset($params['multiple'])) $params['multiple'] = false;
		if (!isset($params['with-undefined'])) $params['with-undefined'] = false;
		if (!isset($params['undefined-value'])) $params['undefined-value'] = '';
		if (!isset($params['undefined-title'])) $params['undefined-title'] = '-- None --';
		if (!isset($params['global-id'])) $params['global-id'] = '';
		if (!isset($params['data-container'])) $params['data-container'] = '';
		if (!isset($params['vertical'])) $params['vertical'] = false;

		if ($required) {
			if (!$params['invalid-feedback']) $params['invalid-feedback'] = \T::Framework_Common_FormRequired();
			// if (!$params['valid-feedback']) $params['valid-feedback'] = \T::Framework_Common_FormLooksGood();
		}

		$html = '';
		if (!$params['vertical']) {
			$html .= '<div class="mb-3 row" '.($params['global-id'] ? 'id="'.$params['global-id'].'"':'').'>';
		}
		$html .= '	<label for="'.$params['id'].'" class="'.$params['class1'].' col-form-label">'.$title.($required ? ' <sup>*</sup>' : '').'</label>';
		$html .= '	<div class="'.$params['class2'].'">';

		$html .= '	<select class="selectpicker" data-mobile="false" data-live-search="true" '.
			($params['data-container'] ? 'data-container="'.$params['data-container'].'" ':'').
			'aria-label="" id="'.$params['id'].'" name="'.$name.'" data-size="20" '.
			($required ? 'required ' : '').
			($params['multiple'] ? 'multiple data-selected-text-format="count" data-actions-box="true" ' : '').
		'>';
		if ($params['with-undefined']) {
			$html .= '		<option value="'.$params['undefined-value'].'">'.$params['undefined-title'].'</option>';
		}
		foreach($data_hash as $k=>$v) {
			$selected = false;
			if (is_array($values)) {
				if (in_array($k, $values)) {
					$selected = true;
				}
			} else {
				if ($k == $values) {
					$selected = true;
				}
			}
			$html .= '		<option value="'.$k.'" '.($selected ? 'selected' : '').'>'.$v.'</option>';
		}
		$html .= '	</select>';
		if ($params['valid-feedback']) {
			$html .= '		<div class="valid-feedback">'.$params['valid-feedback'].'</div>';
		}
		if ($params['invalid-feedback']) {
			$html .= '		<div class="invalid-feedback">'.$params['invalid-feedback'].'</div>';
		}
		$html .= '	</div>';
		if (!$params['vertical']) {
			$html .= '</div>';
		}
		return $html;
	}

	public function html_totp(string $name) {
		?>
		<input type="hidden" class="totp" name="<?=$name;?>" id="<?=$name;?>" value="" autocomplete="one-time-code">
		<div class="otp-field mb-4" id="<?=$name?>_div">
			<input type="number" min="0" max="9" pattern="\d*" id="<?=$name;?>_1" autocomplete="one-time-code"/>
			<input type="number" min="0" max="9" pattern="\d*" id="<?=$name;?>_2" autocomplete="off"/>
			<input type="number" min="0" max="9" pattern="\d*" id="<?=$name;?>_3" autocomplete="off"/>
			-
			<input type="number" min="0" max="9" pattern="\d*" id="<?=$name;?>_4" autocomplete="off"/>
			<input type="number" min="0" max="9" pattern="\d*" id="<?=$name;?>_5" autocomplete="off"/>
			<input type="number" min="0" max="9" pattern="\d*" id="<?=$name;?>_6" autocomplete="off"/>
		</div>
		<script nonce="<?=\CSP::nonceRandom();?>">
			$(document).ready(function() {
				otp_input('<?=$name;?>', '<?=$name;?>_div');
				$('#<?=$name;?>_1').focus();
			});
		</script>
		<?php
	}

	public function __destruct() {
		global $error, $success;
		?>
						</div>
					</main>
					<footer class="footer mt-auto py-3">
						<div class="container-xxl border-top d-flex flex-wrap justify-content-between align-items-center">
							<span class="mt-3 mb-3 text-body-secondary justify-content-start">© 2025 Vadim Babadzhanyan</span>
							<span class="mt-3 mb-3 text-body-secondary justify-content-end"><?=\T::Framework_Version();?>: <?=constant('SERVER_VERSION');?></span>
						</div>
					</footer>
				</div>
				<script nonce="<?=\CSP::nonceRandom();?>">
					$(document).ready(function(){
						showErrorToast('<?=addslashes(str_replace("\n","<br/>",$error));?>');
						showSuccessToast('<?=addslashes(str_replace("\n","<br/>",$success));?>');
						// showAllToasts();
					});
				</script>
			</body></html>
		<?php
	}
}