<?php
namespace ecp;

defined( 'ABSPATH' ) OR exit;

class WP_Integration {
	private $_enhanced_category;
	private $_plugin_url;
	private $_translation_domain;
	private $_after_init_callback;
	private $_plugin_file_path;
	private $_table_name;

	private function __construct($plugin_url, $plugin_directory_name, $plugin_file_path) {
		$this->_plugin_url = $plugin_url;
		$this->_translation_domain = $plugin_directory_name;
		$this->_plugin_file_path = $plugin_file_path;
		$this->_table_name = "ecp_x_category";
	}

	public static function getInstance($plugin_url, $plugin_directory_name, $plugin_file_path) {
		static $instance = null;
		if (null === $instance) {
			$instance = new static($plugin_url, $plugin_directory_name, $plugin_file_path);
		}

		return $instance;
	}

	public function get_ec() {
		return $this->_enhanced_category;
	}

	public function after_init($callback) {
		$this->_after_init_callback = $callback;
	}

	public function install() {

		//create table for category ecp correlaction
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $table_name = $wpdb->prefix . $this->_table_name;

		$sql = "CREATE TABLE {$table_name} (
		  id bigint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
		  category_id bigint(9) UNSIGNED NOT NULL UNIQUE,
		  post_id bigint(9) UNSIGNED NOT NULL UNIQUE,
		  PRIMARY KEY  id (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	public function load_translations() {

		load_plugin_textdomain($this->_translation_domain, FALSE, $this->_translation_domain . '/languages/');
	}

	public function init() {

		//i18n
		add_action('plugins_loaded', array(&$this, "load_translations"));

		//plugin is activated hook
		register_activation_hook($this->_plugin_file_path, array(&$this, "install"));

		add_action("init", array(&$this, "register_custom_post"));
		add_filter("tag_row_actions", array(&$this, 'add_ec_edit_link'), 10, 2);
		add_action("create_category", array(&$this, 'add_category'));
		add_action('edit_category_form_fields', array(&$this, 'category_edit_form_fields'));
		add_action("pre_delete_term", array(&$this, 'delete_category'), 10, 2);
		add_action("edited_category", array(&$this, 'update_category'));
		add_action("post_updated", array(&$this, 'update_post'));
		//customize admin area
		add_action("admin_init", array(&$this, 'admin_init'));
	}

	public function category_edit_form_fields($tag) {
		$url = $this->get_enhanced_category_edit_url($tag);
		echo '<input type="hidden" id="enhanced_category_edit_url" value="' . esc_url($url) . '" />';
	}

	public function admin_init() {
		add_action("admin_head", array(&$this, 'admin_head'));

		$this->register_scripts();
	}

	public function register_scripts() {
		// Register the script
		$js_handle = 'ecp-admin';

		wp_register_script($js_handle, $this->_plugin_url . '/scripts/admin.js');

		// Localize the script with new data
		$translation_array = array(
			'back_to_categories' => __('Back to categories', $this->_translation_domain),
			'back_to_categories_url' => esc_url(admin_url("edit-tags.php?taxonomy=category")),
			'post_type_name' => $this->_enhanced_category->get_safe_name(),
			'edit_enhanced' => __("Enhanced Edit", $this->_translation_domain),
		);

		wp_localize_script($js_handle, 'ecp_js_l10n', $translation_array);

		wp_enqueue_script($js_handle);
	}

	public function admin_head() {
		wp_enqueue_style('stylsheet', $this->_plugin_url . '/css/admin.css');
	}

	public function register_custom_post() {
		$this->_enhanced_category = new Enhanced_Category($this->_translation_domain, $this->_table_name);

		if (is_callable($this->_after_init_callback)) {
			call_user_func($this->_after_init_callback);
		}
	}

	public function add_ec_edit_link($actions, $tag) {

		if ($tag->taxonomy !== 'category') {
			return $actions;
		}

		$actions['_enhanced_category_edit'] = $this->get_enhanced_category_edit_link($tag);

		return $actions;
	}

	private function get_enhanced_category_edit_link($tag) {
		return '<a href="' . $this->get_enhanced_category_edit_url($tag) . '">' . __("Enhanced Edit", $this->_translation_domain) . "</a>";
	}

	private function get_enhanced_category_edit_url($tag) {
		$url = "";

		$post_id = $this->_enhanced_category->get_first_or_create_for_category($tag->term_id);

		if (!empty($post_id)) {
			$url = admin_url("post.php?post={$post_id}&action=edit");
		}

		return $url;
	}

	public function add_category($category_id) {
		$cat = get_category($category_id);
		return $this->_enhanced_category->add_new_from_category($cat);
	}

	public function delete_category($category_id, $taxonomy) {
		if ($taxonomy !== 'category') {
			return;
		}
		$this->_enhanced_category->delete_category($category_id);
	}

	public function update_category($category_id) {
		$cat = get_category($category_id);

		//always update title and slug using the category
		$this->_enhanced_category->update_from_category($cat);
	}

	public function update_post($post_id) {
		$post = get_post($post_id);
		if ($post->post_type === $this->_enhanced_category->get_safe_name()) {
			$this->_enhanced_category->update_from_post($post_id);
		}
	}
}
