=== Enhanced Category Pages ===
Contributors: cip, dioneea
Tags: categories, taxonomy, term, page, enhanced, custom post, custom post type, category, featured image
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=7K3XA4WQ2BUVJ&lc=US&item_name=Enhanced%20Category%20Wordpress%20Plugin&item_number=Support%20Open%20Source&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create custom enhanced pages for categories and any taxonomy term and manage them as a custom post.

== Description ==

Enhanced Category Pages allows you to create custom category and term pages by managing them using a special custom post type.

**Features**

* **NEW** Enhance any taxonomy: edit **any taxonomy** term as a custom post
* edit category as a custom post - *Enhanced Category*
* automatically generates *Enhanced Category* post type for each category
* transparent synchronization of *Enhanced Category* and it's corresponding category
* add any features available to WordPress custom posts
* easy *Enhanced Category* display on category template using `<?php $GLOBALS['enhanced_category']->setup_ec_data(); ?>` (see install section)
* internationalization ready

**Future Features**

* customize *Enhanced Category* custom post type capabilities via plugin options
* manual selection on enhanced categories


== Installation ==
1. Download plugin archive.
2. Upload and uncompress it in "/wp-content/plugins/" directory.
3. Activate the plugin through the "Plugins" menu in WordPress.
4. Use "Enhanced Edit" link to edit the page of the respective category
5. Edit **category/taxonomy template** to show the content of the "Enhanced Category":
`
    //in category.php or taxonomy.php
    <?php
        global $enhanced_category;
        //get enhanced category post and set it up as global current post
        $enhanced_category->setup_ec_data();
    ?>
    <!-- enhanced category content -->
    <?php the_post_thumbnail("medium"); ?>

    <?php get_template_part( 'content', 'page' ); ?>

    <!-- custom fields -->
    <?php
        get_post_custom();
    ?>

    <?php
        // If comments are open or we have at least one comment, load up the comment template
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif;
    ?>
`

== Frequently Asked Questions ==

= What custom post type is created? =

*Enhanced Category* (safe name: enhancedcategory) custom post type is created and a post is generated automatically for each category.

= What happens if I edit the category fields? =

*Enhanced Category* Post (ECP) is synchronized in both directions with it's corresponding category i.e. category name - ECP title, category slug - ECP slug, category description - ECP excerpt.

= What happens with *Enhanced Category* posts when the plugin is uninstalled? =

*Enhanced Category* posts are deleted when the plugin is deleted using the WordPress plugin management page. Note: nothing is deleted when the plugin deactivated.


== Screenshots ==
1. Enhanced Edit link in category list
2. Enhanced Edit link in category edit
3. Enhanced Category custom post type edit
4. Category public view

== Changelog ==

= 0.1 =
* Initial release.

= 0.2 =
* Make php 5.3 compatible.

= 1.0 =
* Enhance any taxonomy

= 1.0.1 =
* bug fixing


== Upgrade Notice ==

= 0.2 =
* This version adds support for 5.3

= 1.0 =
* Enhance a term from any taxonomy

= 1.0.1 =
* Bugs fixed
