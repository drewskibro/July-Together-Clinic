(function ($) {
	'use strict';

	if (typeof $ === 'undefined') return;

	function prefill() {
		var data = window.tcCheckoutPrefill;
		if (!data || !data.hasData) return;

		var map = {
			'#billing_first_name':  data.firstName,
			'#billing_last_name':   data.lastName,
			'#billing_email':       data.email,
			'#billing_phone':       data.phone,
			'#billing_address_1':   data.address_1,
			'#billing_address_2':   data.address_2,
			'#billing_city':        data.city,
			'#billing_postcode':    data.postcode,
			'#shipping_first_name': data.firstName,
			'#shipping_last_name':  data.lastName,
			'#shipping_address_1':  data.address_1,
			'#shipping_address_2':  data.address_2,
			'#shipping_city':       data.city,
			'#shipping_postcode':   data.postcode
		};

		Object.keys(map).forEach(function (sel) {
			if (!map[sel]) return;
			var $el = $(sel);
			if ($el.length && !$el.val()) {
				$el.val(map[sel]).trigger('change');
			}
		});
	}

	$(document).ready(prefill);
	$(document.body).on('updated_checkout', prefill);
})(window.jQuery);
