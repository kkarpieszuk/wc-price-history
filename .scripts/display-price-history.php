#!/usr/bin/env php
<?php
/**
 * Display product price history from wc-price-history export JSON in human-readable format.
 *
 * Usage:
 *   php display-price-history.php [path/to/export.json]
 *
 * If no path is given, looks for a .json file in the current working directory
 * (ignores composer.json, package.json, package-lock.json). If multiple .json
 * files are found, prompts to choose one. If none found, prompts to paste JSON
 * (paste with Ctrl+Shift+V, then Ctrl+D to finish).
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$read_stdin = false;

if ( $argc >= 2 ) {
	$json_path = $argv[1];
} else {
	$candidates = find_json_files_in_cwd();
	if ( count( $candidates ) === 0 ) {
		$json_path = null;
	} elseif ( count( $candidates ) === 1 ) {
		$json_path = $candidates[0];
	} else {
		$json_path = prompt_choose_file( $candidates );
	}
}

if ( $json_path && is_readable( $json_path ) ) {
	$raw = file_get_contents( $json_path );
} else {
	if ( $argc < 2 ) {
		fwrite( STDERR, "No path given and no .json file found in the current directory.\n" );
	}
	fwrite( STDERR, "Paste JSON content (e.g. Ctrl+Shift+V), then press Ctrl+D to finish:\n\n" );
	$raw         = stream_get_contents( STDIN );
	$read_stdin  = true;
	if ( trim( $raw ) === '' ) {
		fwrite( STDERR, "Error: No input. Provide a file path, paste JSON, or run in a directory with a .json file.\n" );
		exit( 1 );
	}
}
$json = json_decode( $raw, true );

if ( json_last_error() !== JSON_ERROR_NONE ) {
	$src = $read_stdin ? 'pasted content' : $json_path;
	fwrite( STDERR, "Error: Invalid JSON ({$src}).\n" );
	exit( 1 );
}

if ( empty( $json['serialized'] ) ) {
	fwrite( STDERR, "Error: JSON does not contain 'serialized' (not a wc-price-history export?).\n" );
	exit( 1 );
}

$export_data = @unserialize( $json['serialized'] );

if ( $export_data === false && $json['serialized'] !== serialize( false ) ) {
	fwrite( STDERR, "Error: Could not unserialize export data.\n" );
	exit( 1 );
}

$product_name = $json['product_name'] ?? ( $export_data['product']['product_name'] ?? 'Unknown product' );

echo "Product analysis: " . $product_name . "\n";

$histories = [];

if ( ! empty( $export_data['product']['history'] ) && is_array( $export_data['product']['history'] ) ) {
	$histories[] = [ 'name' => $product_name, 'history' => $export_data['product']['history'] ];
}

if ( ! empty( $export_data['variations'] ) ) {
	foreach ( $export_data['variations'] as $variation ) {
		if ( ! empty( $variation['history'] ) && is_array( $variation['history'] ) ) {
			$histories[] = [ 'name' => $variation['product_name'], 'history' => $variation['history'] ];
		}
	}
}

if ( empty( $histories ) ) {
	echo "No price history recorded.\n";
	exit( 0 );
}

$now = time();

foreach ( $histories as $item ) {
	if ( count( $histories ) > 1 ) {
		echo "\n--- " . $item['name'] . " ---\n";
	}
	$history = $item['history'];
	ksort( $history, SORT_NUMERIC );
	$history = array_reverse( $history, true );
	foreach ( $history as $ts => $price ) {
		$ago = time_ago( $now - (int) $ts );
		$price_str = number_format( (float) $price, 2, '.', ',' );
		echo $ago . ": " . $price_str . "\n";
	}
}

/**
 * Find .json files in current working directory, excluding composer/package files.
 *
 * @return list<string> Paths to .json files (may be empty).
 */
function find_json_files_in_cwd() {
	$ignored = [ 'composer.json', 'package.json', 'package-lock.json' ];
	$cwd     = getcwd();
	$all     = glob( $cwd . '/*.json' ) ?: [];
	$files   = [];
	foreach ( $all as $path ) {
		$base = basename( $path );
		if ( ! in_array( $base, $ignored, true ) ) {
			$files[] = $path;
		}
	}
	sort( $files );
	return $files;
}

/**
 * Print numbered list of files and read user choice from stdin.
 *
 * @param list<string> $paths Full paths to .json files.
 * @return string|null Selected path or null if invalid/empty input.
 */
function prompt_choose_file( array $paths ) {
	fwrite( STDERR, "Found multiple .json files. Which one to use?\n\n" );
	foreach ( $paths as $i => $path ) {
		$num = $i + 1;
		$base = basename( $path );
		fwrite( STDERR, "  {$num}) {$base}\n" );
	}
	fwrite( STDERR, "\nEnter number (1-" . count( $paths ) . "): " );
	$line = fgets( STDIN );
	if ( $line === false ) {
		return null;
	}
	$choice = (int) trim( $line );
	if ( $choice < 1 || $choice > count( $paths ) ) {
		fwrite( STDERR, "Invalid choice.\n" );
		return null;
	}
	return $paths[ $choice - 1 ];
}

/**
 * Format seconds as "X time ago" in English.
 *
 * @param int $seconds Seconds in the past (positive = past).
 * @return string Human-readable phrase like "17 minutes, 15 seconds ago".
 */
function time_ago( $seconds ) {
	if ( $seconds < 0 ) {
		$seconds = 0;
	}
	$days    = (int) ( $seconds / 86400 );
	$hours   = (int) ( ( $seconds % 86400 ) / 3600 );
	$minutes = (int) ( ( $seconds % 3600 ) / 60 );
	$secs    = (int) ( $seconds % 60 );

	$parts = [];
	if ( $days > 0 ) {
		$parts[] = plural_en( $days, 'day', 'days' );
	}
	if ( $hours > 0 ) {
		$parts[] = plural_en( $hours, 'hour', 'hours' );
	}
	if ( $minutes > 0 ) {
		$parts[] = plural_en( $minutes, 'minute', 'minutes' );
	}
	if ( $secs > 0 || empty( $parts ) ) {
		$parts[] = plural_en( $secs, 'second', 'seconds' );
	}

	$last = array_pop( $parts );
	$str  = $parts ? implode( ' ', $parts ) . ' and ' . $last : $last;
	return $str . ' ago';
}

/**
 * English plural: 1 X, N Xs.
 *
 * @param int    $n    Count.
 * @param string $one  Singular form (e.g. "minute").
 * @param string $many Plural form (e.g. "minutes").
 * @return string Count + correct form.
 */
function plural_en( $n, $one, $many ) {
	$n = (int) $n;
	return $n === 1 ? '1 ' . $one : $n . ' ' . $many;
}
