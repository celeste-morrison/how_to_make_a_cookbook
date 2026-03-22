<?php
/**
 * Server-side render for the Recipe Index block.
 * Queries published posts that contain the cookbook/cookbook-recipes block.
 */

$posts = get_posts( [
	'post_type'      => [ 'post', 'page' ],
	'post_status'    => 'publish',
	'numberposts'    => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

$recipe_data = [];
$categories  = [];

foreach ( $posts as $post ) {
	if ( ! has_blocks( $post->post_content ) ) {
		continue;
	}

	$recipe_block = null;
	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( 'cookbook/cookbook-recipes' === $block['blockName'] ) {
			$recipe_block = $block;
			break;
		}
	}

	if ( ! $recipe_block ) {
		continue;
	}

	$attrs       = $recipe_block['attrs'];
	$recipe_name = ! empty( $attrs['recipeName'] )
		? wp_strip_all_tags( $attrs['recipeName'] )
		: $post->post_title;

	// Use the post's categories for filtering.
	$terms = get_the_category( $post->ID );
	$cats  = [];
	foreach ( $terms as $term ) {
		$cats[] = $term->name;
		if ( ! in_array( $term->name, $categories, true ) ) {
			$categories[] = $term->name;
		}
	}

	// Extract plain-text ingredients for search.
	$ingredients_text = ! empty( $attrs['ingredients'] )
		? wp_strip_all_tags( $attrs['ingredients'] )
		: '';

	$recipe_data[] = [
		'name'        => $recipe_name,
		'categories'  => $cats,
		'url'         => get_permalink( $post->ID ),
		'ingredients' => $ingredients_text,
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
