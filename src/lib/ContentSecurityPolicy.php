<?php

class ContentSecurityPolicy  {
	static private string $nonceRandom = '';
	static private array $default_src = [];
	static private array $script_src = [];
	static private array $style_src = [];
	static private array $img_src = [];
	static private array $font_src = [];
	static private array $base_uri = [];
	static private array $form_action = [];
	static private array $connect_src = [];
	static private array $object_src = [];
	static private array $frame_src = [];
	static private array $media_src = [];
	static private array $child_src = [];

	static public function nonceRandom(): string {
		if (!static::$nonceRandom) {
			static::$nonceRandom = (string) random_int(1, 10000000000);
		}
		return static::$nonceRandom;
	}

	static public function init(): void {
		static::add_default_src("'self'");

		static::add_script_src("'self'");
		static::add_script_src("'nonce-".static::nonceRandom()."'");

		static::add_style_src("'self'");
		static::add_style_src("'nonce-".static::nonceRandom()."'");

		static::add_img_src("'self'");
		static::add_img_src("blob:");
		static::add_img_src("data:");
		static::add_img_src("https://gravatar.com/avatar/"); // Gravatar

		static::add_font_src("'self'");
		static::add_font_src("data:");

		static::add_base_uri("'self'");

		static::add_form_action("*");
		static::add_form_action("'self'");

		static::add_connect_src("'self'");

		static::add_object_src("'self'");
		static::add_object_src("blob:");
		static::add_object_src("data:");

		static::add_frame_src("'none'");

		static::add_media_src("'self'");
		static::add_child_src("'self'");
	}

	// Print Header
	static public function print_header(): void {
		$csp = '';
		if (static::$default_src) $csp .= 'default-src '.implode(' ', static::$default_src).' ; ';
		if (static::$font_src) $csp .= 'font-src '.implode(' ', static::$font_src).' ; ';
		if (static::$base_uri) $csp .= 'base-uri '.implode(' ', static::$base_uri).' ; ';
		if (static::$form_action) $csp .= 'form-action '.implode(' ', static::$form_action).' ; ';
		if (static::$script_src) $csp .= 'script-src '.implode(' ', static::$script_src).' ; ';
		if (static::$style_src) $csp .= 'style-src '.implode(' ', static::$style_src).' ; ';
		if (static::$connect_src) $csp .= 'connect-src '.implode(' ', static::$connect_src).' ; ';
		if (static::$img_src) $csp .= 'img-src '.implode(' ', static::$img_src).' ; ';
		if (static::$object_src) $csp .= 'object-src '.implode(' ', static::$object_src).' ; ';
		if (static::$frame_src) $csp .= 'frame-src '.implode(' ', static::$frame_src).' ; ';
		if (static::$media_src) $csp .= 'media-src '.implode(' ', static::$media_src).' ; ';
		if (static::$child_src) $csp .= 'child-src '.implode(' ', static::$child_src).' ; ';
		if ($csp) header('Content-Security-Policy: '.$csp);
	}

	// Default src
	static public function get_default_src(): array {
		return static::$default_src;
	}
	static public function remove_default_src(): void {
		static::$default_src = [];
	}
	static public function set_default_src(array $src): void {
		static::$default_src = $src;
	}
	static public function add_default_src(string $src): void {
		static::$default_src[] = $src;
	}

	// Script src
	static public function get_script_src(): array {
		return static::$script_src;
	}
	static public function remove_script_src(): void {
		static::$script_src = [];
	}
	static public function set_script_src(array $src): void {
		static::$script_src = $src;
	}
	static public function add_script_src(string $src): void {
		static::$script_src[] = $src;
	}

	// Style src
	static public function get_style_src(): array {
		return static::$style_src;
	}
	static public function remove_style_src(): void {
		static::$style_src = [];
	}
	static public function set_style_src(array $src): void {
		static::$style_src = $src;
	}
	static public function add_style_src(string $src): void {
		static::$style_src[] = $src;
	}

	// Img src
	static public function get_img_src(): array {
		return static::$img_src;
	}
	static public function remove_img_src(): void {
		static::$img_src = [];
	}
	static public function set_img_src(array $src): void {
		static::$img_src = $src;
	}
	static public function add_img_src(string $src): void {
		static::$img_src[] = $src;
	}

	// Font src
	static public function get_font_src(): array {
		return static::$font_src;
	}
	static public function remove_font_src(): void {
		static::$font_src = [];
	}
	static public function set_font_src(array $src): void {
		static::$font_src = $src;
	}
	static public function add_font_src(string $src): void {
		static::$font_src[] = $src;
	}

	// Base uri
	static public function get_base_uri(): array {
		return static::$base_uri;
	}
	static public function remove_base_uri(): void {
		static::$base_uri = [];
	}
	static public function set_base_uri(array $src): void {
		static::$base_uri = $src;
	}
	static public function add_base_uri(string $src): void {
		static::$base_uri[] = $src;
	}

	// Form action
	static public function get_form_action(): array {
		return static::$form_action;
	}
	static public function remove_form_action(): void {
		static::$form_action = [];
	}
	static public function set_form_action(array $src): void {
		static::$form_action = $src;
	}
	static public function add_form_action(string $src): void {
		static::$form_action[] = $src;
	}

	// Connect src
	static public function get_connect_src(): array {
		return static::$connect_src;
	}
	static public function remove_connect_src(): void {
		static::$connect_src = [];
	}
	static public function set_connect_src(array $src): void {
		static::$connect_src = $src;
	}
	static public function add_connect_src(string $src): void {
		static::$connect_src[] = $src;
	}

	// Object src
	static public function get_object_src(): array {
		return static::$object_src;
	}
	static public function remove_object_src(): void {
		static::$object_src = [];
	}
	static public function set_object_src(array $src): void {
		static::$object_src = $src;
	}
	static public function add_object_src(string $src): void {
		static::$object_src[] = $src;
	}

	// Frame src
	static public function get_frame_src(): array {
		return static::$frame_src;
	}
	static public function remove_frame_src(): void {
		static::$frame_src = [];
	}
	static public function set_frame_src(array $src): void {
		static::$frame_src = $src;
	}
	static public function add_frame_src(string $src): void {
		static::$frame_src[] = $src;
	}

	// Media src
	static public function get_media_src(): array {
		return static::$media_src;
	}
	static public function remove_media_src(): void {
		static::$media_src = [];
	}
	static public function set_media_src(array $src): void {
		static::$media_src = $src;
	}
	static public function add_media_src(string $src): void {
		static::$media_src[] = $src;
	}

	// Child src
	static public function get_child_src(): array {
		return static::$child_src;
	}
	static public function remove_child_src(): void {
		static::$child_src = [];
	}
	static public function set_child_src(array $src): void {
		static::$child_src = $src;
	}
	static public function add_child_src(string $src): void {
		static::$child_src[] = $src;
	}

}