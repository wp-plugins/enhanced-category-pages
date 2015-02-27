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
		  taxonomy varchar(100) NOT NULL,
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
		add_action("create_term", array(&$this, 'add_category'), 10, 3);

		add_action("pre_delete_term", array(&$this, 'delete_category'), 10, 2);
		add_action("edited_term", array(&$this, 'update_category'), 10, 3);
		add_action("post_updated", array(&$this, 'update_post'));
		//customize admin area
		add_action("admin_init", array(&$this, 'admin_init'), 10000);
	}

	public function category_edit_form_fields($tag) {
		$url = $this->get_enhanced_category_edit_url($tag);
		echo '<input type="hidden" id="enhanced_category_edit_url" value="' . esc_url($url) . '" />';

		$enhanced_edit_text = __("Enhanced Edit", $this->_translation_domain);
		echo <<<STR
				<script type="text/javascript">
					(function($) {
						var url = $('#enhanced_category_edit_url').val();
						if (url) {
							$('.edit-tags-php .wrap > h2').append(
								'<a href="' + url + '" class="add-new-h2 back-to-categories">{$enhanced_edit_text}</a>'
								);
						}
					})(jQuery);
				</script>

STR;

	}

	public function admin_init() {
		add_action("admin_head", array(&$this, 'admin_head'));

		$this->register_scripts();


		//register for all taxonomies
		$taxonomies = $this->get_enabled_taxonomies();

		//add hidden input url on edit term page
		foreach ($taxonomies as $taxonomy_name) {
			add_action("{$taxonomy_name}_edit_form_fields", array(&$this, 'category_edit_form_fields'));
		}


		//add hidden link to go back to taxonomy list edit
		add_action('edit_form_top', array(&$this, 'ecp_edit_back_taxonomy'));

	}

	public function ecp_edit_back_taxonomy($post) {

		//do only for ecp  posts
		if ( $post->post_type == $this->_enhanced_category->get_safe_name()) {

			$post_id = $post->ID;
			$category = $this->_enhanced_category->get_by_post($post_id);
			$taxonomy = get_taxonomy($category->taxonomy);

			$a = '<a class="add-new-h2 back-to-categories" href="'
				. esc_url(admin_url("edit-tags.php?taxonomy={$category->taxonomy}")) . '">'
				. __("Back to {$taxonomy->labels->name}", $this->_translation_domain) . "</a>";

			echo '<input type="hidden" id="enhanced_category_list_edit_url" value="' . htmlentities($a) . '" />';

			echo '<input type="hidden" id="taxonomy_single_name" value="' . htmlentities($taxonomy->labels->singular_name) . '" />';

			//HACK: added here for responsivness - no delay when replacing
			$post_type_name = $post->post_type;
			echo <<<STR
					<script type="text/javascript">
						(function($) {
							$('.post-php.post-type-{$post_type_name} .wrap > h2').append(
										$('#enhanced_category_list_edit_url').val()
							);

							//replace taxonomy Category with custom taxonomy name
							var h2 = $('h2');
							var html = h2.html().replace('Category', $('#taxonomy_single_name').val());
							h2.html(html);
						})(jQuery);
					</script>

STR;
		}
	}

	public function register_scripts() {
		//EMPTY since 1.0
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

		if ($this->is_valid_taxonomy($tag->taxonomy)) {
			$actions['_enhanced_category_edit'] = $this->get_enhanced_category_edit_link($tag);
		}

		return $actions;
	}

	private function get_enhanced_category_edit_link($tag) {
		return '<a href="' . $this->get_enhanced_category_edit_url($tag) . '">' . __("Enhanced Edit", $this->_translation_domain) . "</a>";
	}

	private function get_enhanced_category_edit_url($tag) {
		$url = "";

		$post_id = $this->_enhanced_category->get_first_or_create_for_category($tag->term_id, $tag->taxonomy);

		if (!empty($post_id)) {
			$url = admin_url("post.php?post={$post_id}&action=edit");
		}

		return $url;
	}

	public function add_category($term_id, $tt_id, $taxonomy) {

		$term = get_term($term_id, $taxonomy);

		return $this->_enhanced_category->add_new_from_category($term);
	}

	public function delete_category($category_id, $taxonomy) {
		$this->_enhanced_category->delete_category($category_id, $taxonomy);
	}

	public function update_category($category_id, $tt_id, $taxonomy) {
		$cat = get_term($category_id, $taxonomy);

		//always update title and slug using the category
		$this->_enhanced_category->update_from_category($cat);
	}

	public function update_post($post_id) {
		$post = get_post($post_id);
		if ($post->post_type === $this->_enhanced_category->get_safe_name()) {
			$this->_enhanced_category->update_from_post($post_id);
		}
	}

	//returns all taxonomies that are enabled for this plugin to enhance
	private function get_enabled_taxonomies() {

		//get taxonomies names
		$taxonomies = get_taxonomies(NULL, 'names');

		$taxonomies  = array_filter($taxonomies, array($this, 'is_valid_taxonomy'));

		return $taxonomies;
	}

	private function is_valid_taxonomy($taxonomy_name) {
		//always true for the moment
		//TODO: should check the user settings
		return true;
	}
}
