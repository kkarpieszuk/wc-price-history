/* global wc_price_history_react_main */

import LowestPriceText from './components/LowestPriceText';

const { createRoot, createElement } = wp.element;
// I18n.
const { __ } = wp.i18n;

const selector = ".wc-price-history.prior-price.lowest";

const domNodes = document.querySelectorAll(selector);

domNodes.forEach(node => {
	const root = createRoot(node);

	root.render(<LowestPriceText {...node.dataset} />);
});
