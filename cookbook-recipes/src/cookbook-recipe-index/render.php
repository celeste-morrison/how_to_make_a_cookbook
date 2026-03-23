<?php
/**
 * Server-side render for the Recipe Index block.
 * Uses Mediavine Create cards as the recipe source.
 */

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT c.id, c.title, c.category
	FROM {$wpdb->prefix}mv_creations c
	WHERE c.title NOT LIKE '% Creation'
	ORDER BY c.title ASC"
);

$recipe_data = [];
$categories  = [];

foreach ( $rows as $row ) {
	// Get ingredients from supplies table.
	$supplies = $wpdb->get_col( $wpdb->prepare(
		"SELECT original_text FROM {$wpdb->prefix}mv_supplies WHERE creation = %d",
		$row->id
	) );

	// Get category term name if set.
	$cat_name = '';
	if ( $row->category ) {
		$term = get_term( (int) $row->category );
		if ( $term && ! is_wp_error( $term ) ) {
			$cat_name = $term->name;
		}
	}

	// Fall back to wprm_course taxonomy on the matching wprm_recipe for category.
	if ( ! $cat_name ) {
		$wprm = $wpdb->get_row( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wprm_recipe' AND post_status = 'publish' AND post_title = %s LIMIT 1",
			$row->title
		) );
		if ( $wprm ) {
			$terms = get_the_terms( $wprm->ID, 'wprm_course' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$cat_name = $terms[0]->name;
			}
		}
	}

	if ( $cat_name && ! in_array( $cat_name, $categories, true ) ) {
		$categories[] = $cat_name;
	}

	$recipe_data[] = [
		'id'          => $row->id,
		'name'        => $row->title,
		'category'    => $cat_name,
		'url'         => home_url( '/recipe/' . sanitize_title( $row->title ) . '/' ),
		'ingredients' => array_filter( $supplies ),
	];
}

sort( $categories );
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'cookbook-recipe-index' ] ); ?>>

	<div class="recipe-main">
		<input
			type="search"
			class="recipe-search"
			placeholder="Search recipes…"
			aria-label="Search recipes"
		>

		<div class="recipe-grid">
			<?php foreach ( $recipe_data as $recipe ) : ?>
				<a
					href="<?php echo esc_url( $recipe['url'] ); ?>"
					class="recipe-card"
					data-name="<?php echo esc_attr( mb_strtolower( $recipe['name'] ) ); ?>"
					data-categories="<?php echo esc_attr( mb_strtolower( $recipe['category'] ) ); ?>"
					data-ingredients="<?php echo esc_attr( mb_strtolower( implode( ' ', $recipe['ingredients'] ) ) ); ?>"
				>
					<span class="recipe-card-name"><?php echo esc_html( $recipe['name'] ); ?></span>
					<?php if ( $recipe['category'] ) : ?>
						<span class="recipe-card-category"><?php echo esc_html( $recipe['category'] ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>

		<p class="recipe-no-results" style="display:none">No recipes found.</p>
	</div>

	<?php if ( ! empty( $categories ) ) : ?>
	<aside class="recipe-sidebar">
		<p class="recipe-sidebar-heading">Categories</p>
		<div class="recipe-categories" role="group" aria-label="Filter by category">
			<button class="category-btn active" data-category="all">All</button>
			<?php foreach ( $categories as $cat ) : ?>
				<button class="category-btn" data-category="<?php echo esc_attr( $cat ); ?>">
					<?php echo esc_html( $cat ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</aside>
	<?php endif; ?>

</div>
