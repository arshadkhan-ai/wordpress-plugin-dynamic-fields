<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFP_Core {

	private static $instance = null;

	public $field_group;
	public $fields;
	public $location;
	public $admin;
	public $rest;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	private function __construct() {}

	private function init() {
		$this->field_group = new DFP_Field_Group();
		$this->fields      = new DFP_Fields();
		$this->location    = new DFP_Location();
		$this->admin       = new DFP_Admin();
		$this->rest        = new DFP_REST();

		add_action( 'add_meta_boxes', array( $this->location, 'render_meta_boxes' ) );
		add_action( 'save_post',      array( 'DFP_Data', 'save_fields' ), 10, 2 );
		add_action( 'init',           array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'dynamic-fields-pro',
			false,
			dirname( DFP_BASENAME ) . '/languages'
		);
	}
}
