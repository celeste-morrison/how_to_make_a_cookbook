import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
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
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Recipe Details', 'cookbook-recipes' ) }>
					<TextControl
						label={ __( 'Prep Time', 'cookbook-recipes' ) }
						value={ prepTime }
						onChange={ ( val ) => setAttributes( { prepTime: val } ) }
						placeholder="e.g. 15 min"
					/>
					<TextControl
						label={ __( 'Cook Time', 'cookbook-recipes' ) }
						value={ cookTime }
						onChange={ ( val ) => setAttributes( { cookTime: val } ) }
						placeholder="e.g. 30 min"
					/>
					<TextControl
						label={ __( 'Servings', 'cookbook-recipes' ) }
						value={ servings }
						onChange={ ( val ) => setAttributes( { servings: val } ) }
						placeholder="e.g. 4"
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps( { className: 'cookbook-recipe' } ) }>
				<RichText
					tagName="h2"
					className="recipe-name"
					value={ recipeName }
					onChange={ ( val ) => setAttributes( { recipeName: val } ) }
					placeholder={ __( 'Recipe Name…', 'cookbook-recipes' ) }
				/>

				<RichText
					tagName="p"
					className="recipe-description"
					value={ description }
					onChange={ ( val ) => setAttributes( { description: val } ) }
					placeholder={ __( 'Brief description of this recipe…', 'cookbook-recipes' ) }
				/>

				{ ( prepTime || cookTime || servings ) && (
					<div className="recipe-meta">
						{ prepTime && <span><strong>{ __( 'Prep:', 'cookbook-recipes' ) }</strong> { prepTime }</span> }
						{ cookTime && <span><strong>{ __( 'Cook:', 'cookbook-recipes' ) }</strong> { cookTime }</span> }
						{ servings && <span><strong>{ __( 'Serves:', 'cookbook-recipes' ) }</strong> { servings }</span> }
					</div>
				) }

				<h3>{ __( 'Ingredients', 'cookbook-recipes' ) }</h3>
				<RichText
					tagName="ul"
					multiline="li"
					className="recipe-ingredients"
					value={ ingredients }
					onChange={ ( val ) => setAttributes( { ingredients: val } ) }
					placeholder={ __( 'Add ingredients, one per line…', 'cookbook-recipes' ) }
				/>

				<h3>{ __( 'Instructions', 'cookbook-recipes' ) }</h3>
				<RichText
					tagName="ol"
					multiline="li"
					className="recipe-instructions"
					value={ instructions }
					onChange={ ( val ) => setAttributes( { instructions: val } ) }
					placeholder={ __( 'Add steps, one per line…', 'cookbook-recipes' ) }
				/>

				<RichText
					tagName="p"
					className="recipe-notes"
					value={ notes }
					onChange={ ( val ) => setAttributes( { notes: val } ) }
					placeholder={ __( 'Notes or tips (optional)…', 'cookbook-recipes' ) }
				/>
			</div>
		</>
	);
}
