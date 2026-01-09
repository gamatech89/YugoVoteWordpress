jQuery(function ($) {

	$('.read-more-btn').on('click', function(event) {
		event.preventDefault();
console.log('load')
		const $descContainer = $(this).closest('.description-container');
		$descContainer.toggleClass('expanded');

	});

});
