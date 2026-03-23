<?php
/**
 * Server-side render for the Recipe Index block.
 * Uses Mediavine Create cards as the recipe source.
 */

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT c.id, c.title, c.object_id
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

	// Get categories from WordPress category taxonomy assigned to the mv_create post.
	$cat_names = [];
	if ( $row->object_id ) {
		$terms = wp_get_object_terms( (int) $row->object_id, 'category' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$cat_names[] = $term->name;
				if ( ! in_array( $term->name, $categories, true ) ) {
					$categories[] = $term->name;
				}
			}
		}
	}

	$recipe_data[] = [
		'id'          => $row->id,
		'name'        => $row->title,
		'categories'  => $cat_names,
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
					data-categories="<?php echo esc_attr( implode( ',', array_map( 'mb_strtolower', $recipe['categories'] ) ) ); ?>"
					data-ingredients="<?php echo esc_attr( mb_strtolower( implode( ' ', $recipe['ingredients'] ) ) ); ?>"
				>
					<span class="recipe-card-name"><?php echo esc_html( $recipe['name'] ); ?></span>
					<?php if ( ! empty( $recipe['categories'] ) ) : ?>
						<span class="recipe-card-category"><?php echo esc_html( implode( ', ', $recipe['categories'] ) ); ?></span>
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
