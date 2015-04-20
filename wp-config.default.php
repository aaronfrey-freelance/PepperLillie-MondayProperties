<?php
/**
 * Default config settings
 *
 * Enter any WordPress config settings that are default to all environments
 * in this file. These can then be overridden in the environment config files.
 *
 * Please note if you add constants in this file (i.e. define statements)
 * these cannot be overridden in environment config files.
 *
 * @package    Studio 24 WordPress Multi-Environment Config
 * @version    1.0
 * @author     Studio 24 Ltd  <info@studio24.net>
 */

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
define('AUTH_KEY', ':v3N;_OxYr!xn=qNyy+eQ!)k#-eT/a_8UKxPn)3CfW@=loqeI5tW(.-BI^UQvQ4@');
define('SECURE_AUTH_KEY', '7Pc6E]DJ?3ptormA~iy=[-s%~z)X^]9hK&`)B]!U3^cPeEV0j;wFqu6f+hDP=z?%');
define('LOGGED_IN_KEY', 'e}*IuA3g;OdnA|!;(R97`X.|M?>M=3o0,>W~}eHR[!d3h?sEg(RtL. =w ?TmI+}');
define('NONCE_KEY', '7_3(+3)DL c5]FX+K{RsukJy|z_+*|-qJ-fQQCFn_GQq?ZjPN.O|Y5eagz3!/h+,');
define('AUTH_SALT', ',u:/{9)NIB$L`sY<C>YpV[vQ U2|`J_~`@+=3IW]&i,$fF%pYDh/1. Gb{:7i./w');
define('SECURE_AUTH_SALT', '2+$`II,w4@g:q-9]hl!ryyZ?a+lqrJ-2bfZ+e@RW.^i]yYV>TLS$SU)^pq3G=kR#');
define('LOGGED_IN_SALT', '48h^W#HU/^y#L1ytga vQn2#HJYnh=`.U&93LPdgA)JHJQsO5.(lm:N+JWWNX<@~');
define('NONCE_SALT', ' `C-0]%l`i)7,9-(U4D=?>&kS?Lr5b$H emoB(8JZO`,U]k_gyzpT`7vgKROQt|R');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');
