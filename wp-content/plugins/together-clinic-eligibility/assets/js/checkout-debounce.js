(function () {
	'use strict';

	var processing = false;
	var resetTimer = null;

	var BUTTON_SELECTORS = [
		'.wc-block-components-checkout-place-order-button',
		'#place_order'
	].join(',');

	document.addEventListener('click', function (e) {
		var button = e.target.closest(BUTTON_SELECTORS);
		if (!button) return;

		if (processing) {
			e.preventDefault();
			e.stopImmediatePropagation();
			e.stopPropagation();
			console.warn('[tc-debounce] Duplicate place-order click blocked');
			return false;
		}

		processing = true;
		button.setAttribute('aria-busy', 'true');

		clearTimeout(resetTimer);
		resetTimer = setTimeout(function () {
			processing = false;
			button.removeAttribute('aria-busy');
		}, 30000);
	}, true);

	document.addEventListener('wc-blocks-checkout-error', function () {
		processing = false;
		clearTimeout(resetTimer);
	});
})();
