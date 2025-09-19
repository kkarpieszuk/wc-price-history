
declare global {
	interface Window {
		wc_price_history_frontend: {
			thousand_separator: string;
			decimal_separator: string;
			decimals: number;
		};
	}

	const jQuery: any;
}

export {};