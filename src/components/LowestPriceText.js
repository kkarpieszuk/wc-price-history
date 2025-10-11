
export default function LowestPriceText( props ) {
	// Format original price to 2 decimal places, it comes as a string.
	const originalPriceFormatted = parseFloat(props.originalPrice).toFixed(2);

	const priceFormatted = `${originalPriceFormatted} ${wc_price_history_react_main.currency_symbol}`;

	const text = wc_price_history_react_main.display_text
	.replace('{days}', wc_price_history_react_main.last_days)
	.replace('{price}', priceFormatted );

	return <div>{
		text
	}</div>;
}