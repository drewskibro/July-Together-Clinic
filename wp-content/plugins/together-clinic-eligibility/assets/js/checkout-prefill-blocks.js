(function () {
	'use strict';

	if (!window.wp || !window.wp.data) return;

	function applyPrefill() {
		var data = window.tcCheckoutPrefill;
		if (!data || !data.hasData || !data.email) return;

		var cartStore = window.wp.data.dispatch && window.wp.data.dispatch('wc/store/cart');
		if (!cartStore || typeof cartStore.setBillingAddress !== 'function') return;

		var address = {
			first_name: data.firstName || '',
			last_name:  data.lastName || '',
			email:      data.email,
			phone:      data.phone || '',
			address_1:  data.address_1 || '',
			address_2:  data.address_2 || '',
			city:       data.city || '',
			postcode:   data.postcode || '',
			country:    data.country || 'GB'
		};

		try {
			cartStore.setBillingAddress(address);
			if (typeof cartStore.setShippingAddress === 'function') {
				cartStore.setShippingAddress(address);
			}
		} catch (e) {
			console.warn('[tc-eligibility] block checkout prefill failed', e);
		}
	}

	function waitForStoreAndPrefill() {
		var attempts = 0;
		var interval = setInterval(function () {
			attempts++;
			var select = window.wp.data.select && window.wp.data.select('wc/store/cart');
			if (select && typeof select.getCartData === 'function') {
				clearInterval(interval);
				applyPrefill();
				return;
			}
			if (attempts > 40) clearInterval(interval);
		}, 250);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', waitForStoreAndPrefill);
	} else {
		waitForStoreAndPrefill();
	}
})();
