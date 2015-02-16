!(function($) {
	$(function() {
		add_back_to_categories_link(ecp_js_l10n.back_to_categories, ecp_js_l10n.back_to_categories_url, ecp_js_l10n.post_type_name);
		add_edit_enhanced_link(ecp_js_l10n.edit_enhanced);

		function add_back_to_categories_link(name, url, post_type_name) {
			$('.post-php.post-type-' + post_type_name + ' .wrap > h2').append(
				'<a href="' + url + '" class="add-new-h2 back-to-categories">' + name + '</a>'
				);
		}

		function add_edit_enhanced_link(name ) {
			var url = $('#enhanced_category_edit_url').val();
			if (url) {
				$('.edit-tags-php .wrap > h2').append(
					'<a href="' + url + '" class="add-new-h2 back-to-categories">' + name + '</a>'
					);
			}

		}
	});
})(jQuery)