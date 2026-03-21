document.querySelectorAll( '.cookbook-recipe-index' ).forEach( ( container ) => {
	const search    = container.querySelector( '.recipe-search' );
	const catBtns   = container.querySelectorAll( '.category-btn' );
	const cards     = container.querySelectorAll( '.recipe-card' );
	const noResults = container.querySelector( '.recipe-no-results' );

	let activeCategory = 'all';
	let searchTerm     = '';

	function applyFilter() {
		let visible = 0;
		cards.forEach( ( card ) => {
			const matchesSearch   = ! searchTerm || card.dataset.name.includes( searchTerm ) || card.dataset.ingredients.includes( searchTerm );
			const matchesCategory =
				activeCategory === 'all' ||
				card.dataset.categories.split( ',' ).includes( activeCategory );
			const show = matchesSearch && matchesCategory;
			card.style.display = show ? '' : 'none';
			if ( show ) visible++;
		} );
		noResults.style.display = visible > 0 ? 'none' : '';
	}

	search.addEventListener( 'input', ( e ) => {
		searchTerm = e.target.value.toLowerCase().trim();
		applyFilter();
	} );

	catBtns.forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			catBtns.forEach( ( b ) => b.classList.remove( 'active' ) );
			btn.classList.add( 'active' );
			activeCategory = btn.dataset.category.toLowerCase();
			applyFilter();
		} );
	} );
} );
