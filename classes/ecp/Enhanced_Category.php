<?php
namespace ecp;

defined( 'ABSPATH' ) OR exit;

class Enhanced_Category extends WP_Custom_Post {

	private $_prevent_update;
	private $_table_categories_posts;
	private $_cp_proprieties;

	public function __construct($translation_domain, $table_categories_posts) {
		$this->_name = 'Enhanced Category';
		$this->_name_plural = 'Enhanced Categories';
		$this->_prevent_update = false;
		$this->_table_categories_posts = $table_categories_posts;

		$this->_cp_proprieties = array(
			'_builtin' => false, // It's a custom post type, not built in
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'),
			'public' => false,
			'show_ui' => false, // UI in admin panel
		);

		parent::__construct($this->_cp_proprieties, $translation_domain);
	}

	public function add_new_from_category($cat) {

		$post = array(
			'post_name' => $cat->slug,
			'post_title' => $cat->cat_name,
			#'post_category' => array($cat->cat_ID),
			'post_status' => 'publish',
			'post_type' => $this->_name,
			'post_content' => $cat->category_description,
			'post_excerpt' => $cat->category_description,
		);

		$post_id = wp_insert_post($post, false);

		//insert into correlation table
		$this->_insert_into_ecp_x_category($cat->cat_ID, $post_id);

		return $post_id;
	}

	private function _insert_into_ecp_x_category($cat_id, $post_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_categories_posts;
		if ($cat_id > 0 && $post_id > 0) {
			$wpdb->insert(
				$table_name,
				array(
					'category_id' => $cat_id,
					'post_id' => $post_id,
				)
			);
		}
	}

	public function delete_category($cat_id) {
		$post_id = $this->get_first_or_create_for_category($cat_id);
		if ($post_id > 0) {
			wp_delete_post($post_id, true);
			$this->_delete_correlation($cat_id);
		}

	}

	private function _delete_correlation($cat_id) {
		if ($cat_id > 0) {
			global $wpdb;
			$table_name = $wpdb->prefix . $this->_table_categories_posts;
			$wpdb->delete($table_name, array('category_id' => $cat_id));
		}
	}

	//get category of the ecp with given id
	public function get_by_post($post_id) {

		global $wpdb;

		$category = null;

		$category_id = $wpdb->get_var($wpdb->prepare(
			"
				SELECT category_id
				FROM {$wpdb->prefix}{$this->_table_categories_posts}
				WHERE post_id = %d
			",
			$post_id
		));

		return $category_id;

	}

	//get the ecp for the category id
	public function get_by_category($category_id) {

		global $wpdb;

		$posts_array = array();

		$post_id = $wpdb->get_var($wpdb->prepare(
			"
				SELECT post_id
				FROM {$wpdb->prefix}{$this->_table_categories_posts}
				WHERE category_id = %d
			",
			$category_id
		));

		if (!empty($post_id)) {
			$posts_array = get_posts(array(
				'p' => $post_id,
				'post_type' => $this->get_name(),
			));
		}

		return $posts_array;
	}

	public function get_first_or_create_for_category($category_id) {

		$posts_array = $this->get_by_category($category_id);

		if (empty($posts_array)) {
			//if the post does not already exist, we create it
			$cat = get_category($category_id);
			$post_id = $this->add_new_from_category($cat);
		} else {
			$post_id = $posts_array[0]->ID;
		}

		return $post_id;
	}

	public function update_from_post($post_id) {
		if ($this->_prevent_update) {
			return;
		}
		$post = get_post($post_id);
		$category_id = $this->get_by_post($post_id);

		if ($category_id > 0) {
			$category = array(
				'cat_ID' => $category_id,
				'category_nicename' => $post->post_name,
				'cat_name' => $post->post_title,
				'category_description' => $post->post_excerpt,
			);

			$this->_prevent_update = true;
			wp_update_category($category);
		}
	}

	//update title, slug and excerpt from category name, slug and description
	public function update_from_category($category) {
		if ($this->_prevent_update) {
			return;
		}
		$post_id = $this->get_first_or_create_for_category($category->term_id);

		if ($post_id > 0) {
			$post = array(
				'ID' => $post_id,
				'post_name' => $category->slug,
				'post_title' => $category->cat_name,
				'post_excerpt' => $category->category_description,
			);

			$this->_prevent_update = true;
			wp_update_post($post, false);

		}
	}

	//gets global current category and setup the global post data
	public function setup_ec_data() {
		global $withcomments, $post;
		//get global category id
		$cur_cat_id = get_cat_id(single_cat_title("", false));

		$ec_object = $this->get_by_category($cur_cat_id)[0];

		//setup ehanced category as global post
		setup_postdata($post = $ec_object);

		if (in_array("comments", $this->_cp_proprieties['supports'])) {
			$withcomments = true;
		}
	}
}
