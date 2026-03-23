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
}
add_action( 'init', 'cookbook_cookbook_recipes_block_init' );

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
