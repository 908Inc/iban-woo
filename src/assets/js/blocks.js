(function () {
	const settings = window.wc.wcSettings.getSetting('opendatabot_iban_data', {});
	const label = window.wp.htmlEntities.decodeEntities(settings.title) || 'IBAN invoice (Opendatabot)';
	const description = window.wp.htmlEntities.decodeEntities(settings.description || '');
	const icon = settings.icon || '';
	const el = window.wp.element.createElement;

	const Content = function () {
		const parts = [];
		if (icon) {
			parts.push(
				el('img', {
					key: 'icon',
					src: icon,
					alt: '',
					style: { maxHeight: '24px', marginRight: '8px', verticalAlign: 'middle' }
				})
			);
		}
		if (description) {
			parts.push(el('span', { key: 'desc' }, description));
		}
		return el('div', null, parts);
	};

	const Label = function () {
		return el('span', null, label);
	};

	const options = {
		name: 'opendatabot_iban',
		label: el(Label, null),
		ariaLabel: label,
		content: el(Content, null),
		edit: el(Content, null),
		canMakePayment: function () { return true; },
		supports: {
			features: settings.supports || ['products']
		}
	};

	window.wc.wcBlocksRegistry.registerPaymentMethod(options);
})();
