<?php
/**
 * Plugin Name: Unreachable i10n strings
 * Plugin URI: https://javorszky.co.uk
 * Description: Helper plugin that will gather all of your unreachable translation strings. The ones that are used before your load plugin textdomain is declared.
 * Author: Gabor Javorszky
 * Author URI: https://javorszky.co.uk
 * Version: 0.0.1
 *
 * Copyright 2016 Gabor Javorszky.  (email : gabor@javorszky.co.uk)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		Unreachable i10n strings
 * @author		Gabor Javorszky
 * @since		0.0.1
 */



class i10n_Unreachable_Translations {

	public static $domain = 'YOUR DOMAIN HERE';

	public static $constant = 'NAME_YOUR_CONSTANT';

	public static $table_name = 'unreachable_translations';

	public static $plugin_file = __FILE__;

	public static function init() {
		register_activation_hook( __FILE__,  __CLASS__ . '::create_database_tables' );
		add_action( 'load_textdomain',       __CLASS__ . '::define_constant' );
		add_filter( 'gettext',               __CLASS__ . '::filter_gettext',    10, 3 );
		add_filter( 'gettext_with_context',  __CLASS__ . '::filter_gettext_x',  10, 4 );
		add_filter( 'ngettext',              __CLASS__ . '::filter_ngettext',   10, 5 );
		add_filter( 'ngettext_with_context', __CLASS__ . '::filter_ngettext_x', 10, 6 );
	}


	public static function create_database_tables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( self::get_schema() );
	}

	public static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		$table = self::$table_name;

		return array(
			"CREATE TABLE {$wpdb->prefix}{$table} (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `hash` varchar(32) NOT NULL DEFAULT '',
			  `call` longtext,
			  `translation` longtext,
			  `text` longtext,
			  `single` longtext,
			  `plural` longtext,
			  `number` int(11) DEFAULT NULL,
			  `domain` longtext,
			  `context` longtext,
			  `file` longtext,
			  `line` longtext,
			  `function` longtext,
			  `backtrace` longtext,
			  `called` int(11) DEFAULT '0',
  			  PRIMARY KEY (`id`),
			  UNIQUE KEY `hash` (`hash`)
			) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8;"
		);
	}

	public static function define_constant( $domain ) {
		if ( self::$domain === $domain && ! defined( self::$constant ) ) {
			define( self::$constant, true );
		}
	}


	public static function does_check_apply( $domain ) {
		// if it's not defined, or
		// if it's false
		// AND the domain is woo subs
		if ( self::$domain !== $domain ) {
			return false;
		}
		if ( ! defined( self::$constant ) ) {
			return true;
		}
		if ( ! self::$constant ) {
			return true;
		}
		return false;
	}


	public static function filter_backtrace( $backtrace ) {
		if ( array_key_exists( 'file', $backtrace ) && false !== strpos( $backtrace['file'], 'content/plugins/woocommerce-subscriptions' ) && in_array( $backtrace['function'], array(
				'__',
				'_e',
				'_x',
				'_ex',
				'_n',
				'_nx',
				'_n_noop',
				'_nx_noop',
				'esc_attr__',
				'esc_html__',
				'esc_attr_e',
				'esc_html_e',
				'esc_attr_x',
				'esc_html_x',
			) ) ) {
			return true;
		}
		return false;
	}


	public static function save_to_database( $data ) {
		global $wpdb;

		$data = array_merge( array(
			'text' => null,
			'single' => null,
			'plural' => null,
			'number' => null,
			'context' => null,
		), $data );

		$relevant = array_filter( $data['backtrace'], __CLASS__ . '::filter_backtrace' );
		sort( $relevant );


		$hash = md5( json_encode( $relevant[0] ) . $data['call'] );

		$table = self::$table_name;

		$sql = "INSERT INTO {$wpdb->prefix}{$table} (`hash`, `call`, `translation`, `text`, `single`, `plural`, `context`, `number`, `domain`, `file`, `line`, `function`, `backtrace`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s ) ON DUPLICATE KEY UPDATE called = called + 1";

		$sql = $wpdb->prepare( $sql, array( $hash, $data['call'], $data['translation'], $data['text'], $data['single'], $data['plural'], $data['context'], $data['number'], 'woocommerce-subscriptions', $relevant[0]['file'], $relevant[0]['line'], $relevant[0]['function'], maybe_serialize( $data['backtrace'] ) ) );
		$wpdb->query( $sql );
	}

	public static function filter_gettext( $translation, $text, $domain ) {
		if ( self::does_check_apply( $domain ) ) {
			$backtrace = debug_backtrace();

			self::save_to_database( array(
				'call' => 'gettext',
				'translation' => $translation,
				'text' => $text,
				'backtrace' => $backtrace,
			) );
		}
		return $translation;

	}

	public static function filter_gettext_x( $translation, $text, $context, $domain ) {
		if ( self::does_check_apply( $domain ) ) {
			$backtrace = debug_backtrace();

			self::save_to_database( array(
				'call' => 'gettext_with_context',
				'translation' => $translation,
				'text' => $text,
				'context' => $context,
				'backtrace' => $backtrace,
			) );
		}
		return $translation;
	}

	public static function filter_ngettext( $translation, $single, $plural, $number, $domain ) {
		if ( self::does_check_apply( $domain ) ) {
			$backtrace = debug_backtrace();

			self::save_to_database( array(
				'call' => 'ngettext',
				'translation' => $translation,
				'single' => $single,
				'plural' => $plural,
				'number' => $number,
				'backtrace' => $backtrace,
			) );
		}
		return $translation;
	}

	public static function filter_ngettext_x( $translation, $single, $plural, $number, $context, $domain ) {
		if ( self::does_check_apply( $domain ) ) {
			$backtrace = debug_backtrace();

			self::save_to_database( array(
				'call' => 'ngettext_with_context',
				'translation' => $translation,
				'single' => $single,
				'plural' => $plural,
				'number' => $number,
				'context' => $context,
				'backtrace' => $backtrace,
			) );
		}
		return $translation;
	}
}
i10n_Unreachable_Translations::init();
