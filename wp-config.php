<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

define('WP_DEFAULT_THEME', 'monday');

define('WP_SITEURL', 'http://' . $_SERVER['SERVER_NAME'] . ':8888/Freelance/PepperLillie-Monday');
define('WP_HOME', 'http://' . $_SERVER['SERVER_NAME'] . ':8888/Freelance/PepperLillie-Monday');

define('WP_CONTENT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/Freelance/PepperLillie-Monday/wp-content');
define('WP_CONTENT_URL', 'http://' . $_SERVER['SERVER_NAME'] . ':8888/Freelance/PepperLillie-Monday/wp-content');

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'monday');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'pkLs+E(ta5H:&9&|-=Zz<8>qG<fOl!j(DFJZC#Ry_^c@cq;-1}h&rX<UQkR&~4@L');
define('SECURE_AUTH_KEY',  'VSv|hGW^A}Tzm$f%#^gg|D.TaBV~NG_m:|%}D{$|6XX!%f|9}S4Sj@ZAz2xgMo$#');
define('LOGGED_IN_KEY',    '9@R/tD@TZ%PeiQ+@(rs/~go8&JQsnNwgE&3 T|l(bq82{/B3YEhRa{:]e4(l>j]Q');
define('NONCE_KEY',        'n|{0?C+xVg`^i^^X*in3/Xf8vi+|H,.LX8|YuV+,;}#tCzhBK9 j2m=gGOQBxnH,');
define('AUTH_SALT',        'Y._c[G0L1zE0`WV$a<g^p6sJ@iR(7w[Cl!g~Y@6,vy4{|s+yb-UCO}@v7Y8_{l_+');
define('SECURE_AUTH_SALT', '!Avu0KV}6Y=J%ID$v`4Tbc1AcT!w;E$H?&3ooGaR64CH5>MlKJQZqZ#Bc4O_$U9J');
define('LOGGED_IN_SALT',   'WIV!0&#WGmC$K+N6lx6A:7}#%4GJ4[kaw_8J4vmu?ZQ:l;gR??q10qR4ZGI~-m_T');
define('NONCE_SALT',       'l$bjZQ&^%BnD|XEtdrpV|.p`#mVROo70-SO9{G{FO{2SL_ j$ZVscu4+rZ4[ds-4');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', true);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
