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
			'post_title' => $cat->name,
			#'post_category' => array($cat->cat_ID),
			'post_status' => 'publish',
			'post_type' => $this->_name,
			'post_content' => $cat->description,
			'post_excerpt' => $cat->description,
		);

		//var_dump($cat);die;

		$post_id = wp_insert_post($post, false);

		//insert into correlation table
		$this->_insert_into_ecp_x_category($cat->term_id, $post_id, $cat->taxonomy);

		return $post_id;
	}

	private function _insert_into_ecp_x_category($cat_id, $post_id, $taxonomy) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_categories_posts;
		if ($cat_id > 0 && $post_id > 0) {
			$wpdb->insert(
				$table_name,
				array(
					'category_id' => $cat_id,
					'post_id' => $post_id,
					'taxonomy' => $taxonomy
				)
			);
		}
	}

	public function delete_category($cat_id, $taxonomy) {
		$post_id = $this->get_first_or_create_for_category($cat_id, $taxonomy);
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

		$category = $wpdb->get_row($wpdb->prepare(
			"
				SELECT category_id, taxonomy
				FROM {$wpdb->prefix}{$this->_table_categories_posts}
				WHERE post_id = %d
			",
			$post_id
		));

		//requried by update from 0.2 to 1.0
		//fill empty taxonomy name
		if (empty($category->taxonomy)) {
			$category->taxonomy = $this->fill_empty_taxonomy($category->category_id);
		}

		return $category;
	}

	private function fill_empty_taxonomy($category_id) {

		global $wpdb;

		$taxonomy = $wpdb->get_var($wpdb->prepare(
			"
				SELECT taxonomy
				FROM {$wpdb->prefix}term_taxonomy
				WHERE term_id = %d
			",
			$category_id
		));

		if ( !empty($taxonomy) ) {
			$wpdb->update( $wpdb->prefix.$this->_table_categories_posts, array('taxonomy' => $taxonomy), array('category_id' => $category_id) );
		}

		return $taxonomy;
	}

	//get the ecp for the category id
	public function get_by_category($category_id) {

		global $wpdb;

		$posts_array = array();

		$categories_posts_row = $wpdb->get_row($wpdb->prepare(
			"
				SELECT post_id, taxonomy
				FROM {$wpdb->prefix}{$this->_table_categories_posts}
				WHERE category_id = %d
			",
			$category_id
		));

		$post_id = $categories_posts_row->post_id;

		//requried by update from 0.2 to 1.0
		//fill empty taxonomy name
		if (empty($categories_posts_row->taxonomy)) {
			$this->fill_empty_taxonomy($category_id);
		}

		if (!empty($post_id)) {
			$posts_array = get_posts(array(
				'p' => $post_id,
				'post_type' => $this->get_name(),
			));
		}

		return $posts_array;
	}

	public function get_first_or_create_for_category($category_id, $taxonomy) {

		$posts_array = $this->get_by_category($category_id);
		if (empty($posts_array)) {
			//if the post does not already exist, we create it
			$cat = get_term($category_id, $taxonomy);
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
		$category_r = $this->get_by_post($post_id);

		if (!empty($category_r)) {
			$category = array(
				'term_id' => $category_r->category_id,
				'slug' => $post->post_name,
				'name' => $post->post_title,
				'description' => $post->post_excerpt,
			);

			$this->_prevent_update = true;
			wp_update_term($category_r->category_id, $category_r->taxonomy, $category);
		}
	}

	//update title, slug and excerpt from category name, slug and description
	public function update_from_category($category) {
		if ($this->_prevent_update) {
			return;
		}
		$post_id = $this->get_first_or_create_for_category($category->term_id, $category->taxonomy);

		if ($post_id > 0) {
			$post = array(
				'ID' => $post_id,
				'post_name' => $category->slug,
				'post_title' => $category->name,
				'post_excerpt' => $category->description,
			);

			$this->_prevent_update = true;
			wp_update_post($post, false);

		}
	}

	//gets global current category or category with given id and setup the global post data
	public function setup_ec_data($cur_cat_id = null) {
		global $withcomments, $post;

		if (empty($cur_cat_id)) {
			//get global category/term id
			$query_var = get_query_var("taxonomy");

			if ( empty($query_var) ) {
				$cur_cat_id	= get_cat_id(single_cat_title("", false));
			} else {
				$term = get_term_by('slug', get_query_var("term"), $query_var);
				$cur_cat_id = $term->term_id;
			}
		}

		$ec_array = $this->get_by_category($cur_cat_id);
		$ec_object = $ec_array[0];

		//setup ehanced category as global post
		setup_postdata($post = $ec_object);

		if (in_array("comments", $this->_cp_proprieties['supports'])) {
			$withcomments = true;
		}
	}
}
