import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const {
		recipeName,
		description,
		prepTime,
		cookTime,
		servings,
		ingredients,
		instructions,
		notes,
	} = attributes;

	return (
		<div { ...useBlockProps.save( { className: 'cookbook-recipe' } ) }>
			<RichText.Content tagName="h2" className="recipe-name" value={ recipeName } />
			<RichText.Content tagName="p" className="recipe-description" value={ description } />

			{ ( prepTime || cookTime || servings ) && (
				<div className="recipe-meta">
					{ prepTime && <span><strong>Prep:</strong> { prepTime }</span> }
					{ cookTime && <span><strong>Cook:</strong> { cookTime }</span> }
					{ servings && <span><strong>Serves:</strong> { servings }</span> }
				</div>
			) }

			<h3>Ingredients</h3>
			<RichText.Content tagName="ul" className="recipe-ingredients" value={ ingredients } />

			<h3>Instructions</h3>
			<RichText.Content tagName="ol" className="recipe-instructions" value={ instructions } />

			{ notes && <RichText.Content tagName="p" className="recipe-notes" value={ notes } /> }
		</div>
	);
}
