<?php
/**
 * Plugin Name:       Cookbook Recipes
 * Description:       A recipe block plugin for our family cookbook
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cookbook-recipes
 *
 * @package Cookbook
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
 * based on the registered block metadata. Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function cookbook_cookbook_recipes_block_init() {
	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	// Allow the built-in category taxonomy to be assigned to Create cards.
	register_taxonomy_for_object_type( 'category', 'mv_create' );
}
add_action( 'init', 'cookbook_cookbook_recipes_block_init' );

/**
 * Admin page for assigning categories to Create cards.
 */
function cookbook_admin_menu() {
	add_menu_page(
		'Recipe Categories',
		'Recipe Categories',
		'manage_options',
		'cookbook-recipe-categories',
		'cookbook_admin_page',
		'dashicons-category',
		30
	);
}
add_action( 'admin_menu', 'cookbook_admin_menu' );

function cookbook_admin_page() {
	global $wpdb;

	// Handle save.
	if ( isset( $_POST['cookbook_nonce'] ) && wp_verify_nonce( $_POST['cookbook_nonce'], 'cookbook_save_categories' ) ) {
		$assignments = isset( $_POST['recipe_category'] ) ? (array) $_POST['recipe_category'] : [];
		// Also clear recipes that had no checkboxes checked (not present in POST).
		$all_posts = get_posts( [ 'post_type' => 'mv_create', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ] );
		foreach ( $all_posts as $pid ) {
			$term_ids = isset( $assignments[ $pid ] ) ? array_map( 'intval', (array) $assignments[ $pid ] ) : [];
			wp_set_object_terms( $pid, $term_ids, 'category' );
		}
		echo '<div class="notice notice-success"><p>Categories saved.</p></div>';
	}

	// Get all Create cards (not the "Creation" shadow posts).
	$posts = get_posts( [
		'post_type'   => 'mv_create',
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby'     => 'title',
		'order'       => 'ASC',
		'exclude'     => $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='mv_create' AND post_title LIKE '% Creation'" ),
	] );

	// Build category options (hierarchical).
	$categories = get_terms( [
		'taxonomy'   => 'category',
		'hide_empty' => false,
		'parent'     => 0,
	] );

	function cookbook_category_options( $terms, $depth = 0 ) {
		$output = '';
		foreach ( $terms as $term ) {
			$output .= '<option value="' . $term->term_id . '">' . str_repeat( '&nbsp;&nbsp;', $depth ) . esc_html( $term->name ) . '</option>';
			$children = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false, 'parent' => $term->term_id ] );
			if ( $children ) {
				$output .= cookbook_category_options( $children, $depth + 1 );
			}
		}
		return $output;
	}

	$cat_options = cookbook_category_options( $categories );

	echo '<div class="wrap"><h1>Recipe Categories</h1>';
	echo '<form method="post">';
	wp_nonce_field( 'cookbook_save_categories', 'cookbook_nonce' );
	echo '<style>.cb-cats label{display:block;margin:2px 0}.cb-cats .depth-1{margin-left:1rem}.cb-cats .depth-2{margin-left:2rem}</style>';
	echo '<table class="widefat striped"><thead><tr><th>Recipe</th><th>Categories</th></tr></thead><tbody>';

	foreach ( $posts as $post ) {
		$current_terms = wp_get_object_terms( $post->ID, 'category', [ 'fields' => 'ids' ] );
		echo '<tr><td>' . esc_html( $post->post_title ) . '</td><td><div class="cb-cats">';

		function cookbook_render_checkboxes( $terms, $post_id, $current_ids, $depth = 0 ) {
			foreach ( $terms as $term ) {
				$checked = in_array( $term->term_id, $current_ids, true ) ? 'checked' : '';
				$class   = $depth > 0 ? 'depth-' . $depth : '';
				echo '<label class="' . $class . '"><input type="checkbox" name="recipe_category[' . $post_id . '][]" value="' . $term->term_id . '" ' . $checked . '> ' . esc_html( $term->name ) . '</label>';
				$children = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false, 'parent' => $term->term_id ] );
				if ( $children ) {
					cookbook_render_checkboxes( $children, $post_id, $current_ids, $depth + 1 );
				}
			}
		}

		cookbook_render_checkboxes(
			get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false, 'parent' => 0 ] ),
			$post->ID,
			(array) $current_terms
		);

		echo '</div></td></tr>';
	}

	echo '</tbody></table>';
	echo '<p><button type="submit" class="button button-primary">Save All</button></p>';
	echo '</form></div>';
}


/**
 * Add rewrite rule for /recipe/{name}/ and register the query var.
 */
function cookbook_recipe_rewrite_rules() {
	add_rewrite_rule( '^recipe/([^/]+)/?$', 'index.php?pagename=recipe&cookbook_recipe_name=$matches[1]', 'top' );
}
add_action( 'init', 'cookbook_recipe_rewrite_rules' );

function cookbook_recipe_query_vars( $vars ) {
	$vars[] = 'cookbook_recipe_name';
	return $vars;
}
add_filter( 'query_vars', 'cookbook_recipe_query_vars' );

/**
 * Shortcode [cookbook_single_recipe] — renders the Mediavine Create card
 * whose title slug matches the /recipe/{name}/ URL.
 */
function cookbook_single_recipe_shortcode() {
	$recipe_name = get_query_var( 'cookbook_recipe_name' );

	if ( ! $recipe_name ) {
		return '<p>Recipe not found.</p>';
	}

	global $wpdb;
	$rows = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}mv_creations WHERE title NOT LIKE '% Creation'" );

	$create_id = 0;
	foreach ( $rows as $row ) {
		if ( sanitize_title( $row->title ) === $recipe_name ) {
			$create_id = (int) $row->id;
			break;
		}
	}

	if ( ! $create_id ) {
		return '<p>Recipe not found.</p>';
	}

	return do_shortcode( '[mv_create key="' . $create_id . '"]' );
}
add_shortcode( 'cookbook_single_recipe', 'cookbook_single_recipe_shortcode' );
