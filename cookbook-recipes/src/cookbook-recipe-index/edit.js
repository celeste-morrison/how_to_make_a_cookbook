import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	return (
		<div { ...useBlockProps( { style: { padding: '1.5rem', background: '#f9f9f9', borderRadius: '6px', textAlign: 'center', color: '#666' } } ) }>
			Recipe Index — search and category filter will appear on the front end.
		</div>
	);
}
