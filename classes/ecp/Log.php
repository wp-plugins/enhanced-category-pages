<?php
namespace ecp;

defined( 'ABSPATH' ) OR exit;

class Log {
	public static function start_logging() {
		ob_start();
	}

	public static function end_logging() {
		file_put_contents("/tmp/ecp.log", ob_get_clean(), FILE_APPEND);
	}
}
