<?php

class CSP {
	static private string $nonceRandom = '';
	private array $default_src = [];
	private array $script_src = [];
	private array $style_src = [];
	private array $img_src = [];
	private array $font_src = [];
	private array $base_uri = [];
	private array $form_action = [];

	private array $connect_src = [];
	private array $object_src = [];
	private array $frame_src = [];
	private array $media_src = [];
	private array $child_src = [];

	static public function nonceRandom() {
		if (!static::$nonceRandom) {
			static::$nonceRandom = random_int(1, 10000000000);
		}
		return static::$nonceRandom;
	}

	public function __construct() {
		$this->header();
	}

	private function default_src() {
		$this->default_src = [
			"'self'",
		];
		return implode(' ',$this->default_src);
	}

	private function script_src() {
		$this->script_src = [
			"'self'",
			"'nonce-".static::nonceRandom()."'",

		];
		return implode(' ',$this->script_src);
	}

	private function style_src() {
		$this->style_src = [
			"'self'",
			"'nonce-".static::nonceRandom()."'",
		];
		return implode(' ',$this->style_src);
	}

	private function img_src() {
		$this->img_src = [
			"'self'",
			"blob:",
			"data:",
			"https://gravatar.com/avatar/", // Gravatar
		];
		return implode(' ',$this->img_src);
	}

	private function font_src() {
		$this->font_src = [
			"'self'",
			"data:",
		];
		return implode(' ',$this->font_src);
	}

	private function base_uri() {
		$this->base_uri = [
			"'self'",
		];
		return implode(' ',$this->base_uri);
	}

	private function form_action() {
		$this->form_action = [
			"*",
			"'self'",
		];
		return implode(' ',$this->form_action);
	}

	private function connect_src() {
		$this->connect_src = [
			"'self'",
		];
		return implode(' ',$this->connect_src);
	}

	private function object_src() {
		$this->object_src = [
			"'self'",
			"blob:",
			"data:",
		];
		return implode(' ',$this->object_src);
	}

	private function frame_src() {
		$this->frame_src = [
			"'none'",
		];
		return implode(' ',$this->frame_src);
	}

	private function media_src() {
		$this->media_src = [
			"'self'",
		];
		return implode(' ',$this->media_src);
	}

	private function child_src() {
		$this->child_src = [
			"'self'",
		];
		return implode(' ',$this->child_src);
	}

	public function header() {
		$csp = "Content-Security-Policy: ";
		$csp .= "default-src ".$this->default_src()." ; ";
		$csp .= "font-src ".$this->font_src()." ; ";
		$csp .= "base-uri ".$this->base_uri()." ; ";
		$csp .= "form-action ".$this->form_action()." ; ";
		$csp .= "script-src ".$this->script_src()." ; ";
		$csp .= "style-src ".$this->style_src()." ; ";
		$csp .= "connect-src ".$this->connect_src()." ; ";
		$csp .= "img-src ".$this->img_src()." ; ";
		$csp .= "object-src ".$this->object_src()." ; ";
		$csp .= "frame-src ".$this->frame_src()." ; ";
		$csp .= "media-src ".$this->media_src()." ; ";
		$csp .= "child-src ".$this->child_src()." ; ";
		header($csp);
	}

}