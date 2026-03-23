<?php
/**
 * Server-side render for the Recipe Index block.
 * Queries Mediavine Create recipe cards from the mv_creations table.
 */

global $wpdb;
$creations_table = $wpdb->prefix . 'mv_creations';
$supplies_table  = $wpdb->prefix . 'mv_supplies';

// Verify Mediavine Create is installed.
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $creations_table ) ) !== $creations_table ) {
	echo '<p>Mediavine Create is not active.</p>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$creations = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, title, category, canonical_post_id FROM {$creations_table} WHERE type = %s ORDER BY title ASC",
		'recipe'
	)
);

$recipe_data = [];
$categories  = [];

foreach ( $creations as $creation ) {
	if ( empty( $creation->canonical_post_id ) ) {
		continue;
	}

	$url = get_permalink( intval( $creation->canonical_post_id ) );
	if ( ! $url ) {
		continue;
	}

	$cats = [];
	if ( ! empty( $creation->category ) ) {
		$term = get_term( intval( $creation->category ), 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$cats[] = $term->name;
			if ( ! in_array( $term->name, $categories, true ) ) {
				$categories[] = $term->name;
			}
		}
	}

	// Fetch ingredient text for full-text search.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$ingredients = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT original_text FROM {$supplies_table} WHERE creation = %d AND type = 'ingredient'",
			intval( $creation->id )
		)
	);

	$recipe_data[] = [
		'name'        => $creation->title,
		'categories'  => $cats,
		'url'         => $url,
		'ingredients' => implode( ' ', $ingredients ),
	];
}

sort( $categories );
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'cookbook-recipe-index' ] ); ?>>

	<div class="recipe-index-controls">
		<input
			type="search"
			class="recipe-search"
			placeholder="Search recipes…"
			aria-label="Search recipes"
		>
		<?php if ( ! empty( $categories ) ) : ?>
		<div class="recipe-categories" role="group" aria-label="Filter by category">
			<button class="category-btn active" data-category="all">All</button>
			<?php foreach ( $categories as $cat ) : ?>
				<button class="category-btn" data-category="<?php echo esc_attr( $cat ); ?>">
					<?php echo esc_html( $cat ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="recipe-grid">
		<?php foreach ( $recipe_data as $recipe ) : ?>
			<a
				href="<?php echo esc_url( $recipe['url'] ); ?>"
				class="recipe-card"
				data-name="<?php echo esc_attr( mb_strtolower( $recipe['name'] ) ); ?>"
				data-categories="<?php echo esc_attr( implode( ',', array_map( 'mb_strtolower', $recipe['categories'] ) ) ); ?>"
				data-ingredients="<?php echo esc_attr( mb_strtolower( $recipe['ingredients'] ) ); ?>"
			>
				<span class="recipe-card-name"><?php echo esc_html( $recipe['name'] ); ?></span>
				<?php if ( ! empty( $recipe['categories'] ) ) : ?>
					<span class="recipe-card-category"><?php echo esc_html( $recipe['categories'][0] ); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>

	<p class="recipe-no-results" style="display:none">No recipes found.</p>

</div>
