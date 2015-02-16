<?php
namespace ecp;

defined( 'ABSPATH' ) OR exit;

abstract class WP_Custom_Post {
	protected $_name;
	protected $_name_plural;
	protected $_translation_domain;
	public $id;

	private $meta;

	public function __construct($register_options = array(), $translation_domain = "") {

		$this->_translation_domain = $translation_domain;

		if ($this->_name) {
			$_uname = ucfirst($this->_name);
			$_uname_plural = ucfirst($this->_name_plural);
			$options = array_merge(array(
				'labels' => array(
					'not_found' => $this->translate('No %s found.', $_uname_plural),
					'search_items' => $this->translate('Search %s', $_uname_plural),
					'add_new_item' => $this->translate('Add new %s', $_uname),
					'view_item' => $this->translate('View %s\'s page', $_uname),
					'edit_item' => $this->translate('Edit %s', $_uname),
				),
				'label' => $this->translate($_uname_plural),
				'singular_label' => $this->translate($_uname),
				'public' => true,
				'show_ui' => true, // UI in admin panel
				'_builtin' => false, // It's a custom post type, not built in
				'capability_type' => 'post',
				'hierarchical' => false,
				'rewrite' => array("slug" => $this->_name), // Permalinks
				'query_var' => $this->_name, // This goes to the WP_Query schema
				'supports' => array('title'/*,'custom-fields'*/), // Let's use custom fields for debugging purposes only
			), $register_options);

			// Register custom post types
			register_post_type($this->_name, $options);

			//add filter to insure the text $_name is displayed when user updates/inserts
			add_filter('post_updated_messages', array(&$this, 'updated_messages'));
		}
	}

	public function updated_messages($messages) {
		global $post, $post_ID;
		$_uname = ucfirst($this->_name);
		$_uname_plural = ucfirst($this->_name_plural);

		$messages[$this->get_safe_name()] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => $this->translate('%s updated. <!--a href="%s">View  %s</a-->', $_uname, esc_url(get_permalink($post_ID)), $this->_name),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => $this->translate('%s updated', $_uname),
			5 => isset($_GET['revision']) ? $this->translate('%s restored to revision from %s', $_uname, wp_post_revision_title((int) $_GET['revision'], false)) : false,
			6 => $this->translate('%s published. <a href="%s">View %s</a>', $_uname, esc_url(get_permalink($post_ID)), $this->_name),
			7 => $this->translate('%s saved.', $_uname),
			8 => $this->translate('%s submitted. <a target="_blank" href="%s">Preview %s</a>', $_uname, esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))), $this->_name),
			9 => $this->translate('%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %s</a>', $_uname, date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID)), $this->_name),
			10 => $this->translate('%s draft updated. <a target="_blank" href="%s">Preview %s</a>', $_uname, esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))), $this->_name),
		);

		return $messages;
	}

	public function get_name() {
		return $this->_name;
	}

	//translates all parameters and then they are sprintf-ed in the first parameter
	protected function translate($str1) {
		$translation = '';

		$args = func_get_args();

		//translate each string received as argument
		array_walk($args, function (&$str, $index, $translation_domain) {
			$str = __($str, $translation_domain);
		}, $this->_translation_domain);

		$translation = call_user_func_array('sprintf', $args);

		return $translation;
	}

	public function get_safe_name() {
		return sanitize_key($this->_name);
	}
}
