#!/usr/bin/env php
<?php
/**
 * CLI: call a WooCommerce REST URL using WC_REST_API_* keys read from wp-config.php (no WordPress bootstrap — no DB).
 *
 * Usage:
 *   php .scripts/fetch-wc-rest-product-price-history.php 'https://example.test/wp-json/wc/v3/products/5769'
 *   php .scripts/fetch-wc-rest-product-price-history.php --wp-config=/path/to/wp-config.php 'https://...'
 *
 * Keys: define( 'WC_REST_API_CONSUMER_KEY', 'ck_...' ) and same for WC_REST_API_CONSUMER_SECRET in that file.
 * Optional: WC_REST_API_CONSUMER_KEY / WC_REST_API_CONSUMER_SECRET in the environment override file values.
 *
 * @package PriorPrice
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

/**
 * Read a simple define( 'NAME', 'value' ) or define( "NAME", "value" ) from wp-config.php without loading WordPress.
 *
 * @param string $wp_config_path Absolute path to wp-config.php.
 * @param string $constant_name  Constant name.
 *
 * @return string Value or empty string if not found.
 */
function wc_price_history_cli_read_config_define( string $wp_config_path, string $constant_name ): string {

	$raw = @file_get_contents( $wp_config_path );
	if ( $raw === false ) {
		return '';
	}
	$name = preg_quote( $constant_name, '/' );
	if ( preg_match( '/define\s*\(\s*[\'"]' . $name . '[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $raw, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Append query args to a URL (no WordPress).
 *
 * @param string               $url  Full URL.
 * @param array<string, string> $args Query parameters.
 *
 * @return string
 */
function wc_price_history_cli_add_query_args( string $url, array $args ): string {

	$sep = strpos( $url, '?' ) !== false ? '&' : '?';
	return $url . $sep . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
}

$wp_config_path = '';
$rest_url        = '';

foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( strpos( $arg, '--wp-config=' ) === 0 ) {
		$wp_config_path = substr( $arg, strlen( '--wp-config=' ) );
	} elseif ( trim( $arg ) !== '' ) {
		$rest_url = trim( $arg );
	}
}

if ( $wp_config_path === '' ) {
	$wp_config_path = dirname( __DIR__, 4 ) . '/wp-config.php';
}

if ( $rest_url === '' || ! preg_match( '#^https?://#', $rest_url ) ) {
	fwrite( STDERR, "Usage: php fetch-wc-rest-product-price-history.php [--wp-config=/path/to/wp-config.php] <full_rest_url>\n" );
	fwrite( STDERR, "Example: php fetch-wc-rest-product-price-history.php 'https://myplugins.local/wp-json/wc/v3/products/5769'\n" );
	exit( 1 );
}

if ( ! is_readable( $wp_config_path ) ) {
	fwrite( STDERR, "Cannot read wp-config.php: {$wp_config_path}\n" );
	exit( 1 );
}

$key    = getenv( 'WC_REST_API_CONSUMER_KEY' ) ?: '';
$secret = getenv( 'WC_REST_API_CONSUMER_SECRET' ) ?: '';
if ( $key === '' ) {
	$key = wc_price_history_cli_read_config_define( $wp_config_path, 'WC_REST_API_CONSUMER_KEY' );
}
if ( $secret === '' ) {
	$secret = wc_price_history_cli_read_config_define( $wp_config_path, 'WC_REST_API_CONSUMER_SECRET' );
}

if ( $key === '' || $secret === '' ) {
	fwrite( STDERR, "Missing WC_REST_API_CONSUMER_KEY / WC_REST_API_CONSUMER_SECRET in {$wp_config_path} (or environment).\n" );
	exit( 1 );
}

$request_url = wc_price_history_cli_add_query_args(
	$rest_url,
	[
		'consumer_key'    => $key,
		'consumer_secret' => $secret,
	]
);

$host = (string) ( parse_url( $rest_url, PHP_URL_HOST ) ?: '' );
$ssl_verify_peer = $host !== '' && ! preg_match( '/\.local$/i', $host );

$body = '';
$code = 0;

if ( function_exists( 'curl_init' ) ) {
	$ch = curl_init( $request_url );
	curl_setopt_array(
		$ch,
		[
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => [ 'Accept: application/json' ],
			CURLOPT_SSL_VERIFYPEER => $ssl_verify_peer,
			CURLOPT_SSL_VERIFYHOST => $ssl_verify_peer ? 2 : 0,
		]
	);
	$body = (string) curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
	curl_close( $ch );
} else {
	$ctx = stream_context_create(
		[
			'http' => [
				'timeout' => 30,
				'header'  => "Accept: application/json\r\n",
			],
			'ssl'  => [
				'verify_peer'      => $ssl_verify_peer,
				'verify_peer_name' => $ssl_verify_peer,
			],
		]
	);
	$body = (string) @file_get_contents( $request_url, false, $ctx );
	$code = 200;
	if ( isset( $http_response_header[0] ) && preg_match( '#\s(\d{3})\s#', $http_response_header[0], $mh ) ) {
		$code = (int) $mh[1];
	}
}

$data = json_decode( $body, true );

if ( $code < 200 || $code >= 300 ) {
	fwrite( STDERR, "HTTP {$code}\n" );
	echo $body . "\n";
	exit( 1 );
}

if ( ! is_array( $data ) ) {
	fwrite( STDERR, "Invalid JSON response.\n" );
	echo $body . "\n";
	exit( 1 );
}

if ( isset( $data['code'] ) ) {
	echo json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
	exit( 1 );
}

$out = [
	'id'                 => $data['id'] ?? null,
	'name'               => $data['name'] ?? null,
	'sku'                => $data['sku'] ?? null,
	'wc_price_history'   => $data['wc_price_history'] ?? '(not present — enable REST options under WooCommerce > Price History)',
];

echo json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
