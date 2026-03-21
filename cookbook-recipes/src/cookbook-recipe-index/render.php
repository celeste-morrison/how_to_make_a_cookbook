<?php
/**
 * Server-side render for the Recipe Index block.
 */

if ( ! function_exists( 'WPRM_Recipe_Manager' ) && ! class_exists( 'WPRM_Recipe_Manager' ) ) {
	echo '<p>WP Recipe Maker is not active.</p>';
	return;
}

$recipes = get_posts( [
	'post_type'      => 'wprm_recipe',
	'post_status'    => 'publish',
	'numberposts'    => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

$recipe_data = [];
$categories  = [];

foreach ( $recipes as $post ) {
	$terms = get_the_terms( $post->ID, 'wprm_course' );
	$cats  = [];

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$cats[] = $term->name;
			if ( ! in_array( $term->name, $categories, true ) ) {
				$categories[] = $term->name;
			}
		}
	}

	$ingredient_names = [];
	$ingredient_groups = get_post_meta( $post->ID, 'wprm_ingredients', true );
	if ( is_array( $ingredient_groups ) ) {
		foreach ( $ingredient_groups as $group ) {
			if ( ! empty( $group['ingredients'] ) && is_array( $group['ingredients'] ) ) {
				foreach ( $group['ingredients'] as $ingredient ) {
					if ( ! empty( $ingredient['name'] ) ) {
						$ingredient_names[] = $ingredient['name'];
					}
				}
			}
		}
	}

	$recipe_data[] = [
		'id'          => $post->ID,
		'name'        => $post->post_title,
		'categories'  => $cats,
		'url'         => home_url( '/recipe/?recipe=' . $post->ID ),
		'ingredients' => $ingredient_names,
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
			data-ingredients="<?php echo esc_attr( mb_strtolower( implode( ' ', $recipe['ingredients'] ) ) ); ?>"
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
