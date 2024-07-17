<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'swykgcfqhosting_nhom1' );

/** Database username */
define( 'DB_USER', 'swykgcfqhosting_nhom1' );

/** Database password */
define( 'DB_PASSWORD', 'Nhom1@123' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'E,H[wtP%Kc-j=ntOo~pF~+lr^uWm!QBg9yZC`8!N73wN$mYbNqg!9@cg7S8NdDRe' );
define( 'SECURE_AUTH_KEY',  'DhAN-K-Oi[f%& w]h+=}D?SVZ>f{GVFzp;3_@POW} d9`NA(q^5.ujm#@eh%)B0X' );
define( 'LOGGED_IN_KEY',    'y)!R+*U3/; q[zNQddVg|68lL4$_S;e1Ln40RPFyS-iTYp50,M>?Os+1=8IotVVJ' );
define( 'NONCE_KEY',        '2pHnzlUMEDA9fuvf[(kEp(jSY2o@DIMXdINf%Ekg: n0#6PkWT+b5LlD.&sETC{-' );
define( 'AUTH_SALT',        'D<Sd1JMP?9tiJLiS[S,Ju<OJw!VB8L sX)9?(Og;9;Fg6%y<W%[qe;L-Xt:Q/4~D' );
define( 'SECURE_AUTH_SALT', 'q]Fk>Y<C#g>2;Ee,]Iz5AVUKn{xPvexx`j-_+z%b{pZZ9+@:zlRm2VfZllE~PRQh' );
define( 'LOGGED_IN_SALT',   'm]K73wW~J`aY>:IjlyEI:8-s)y?zTq6Q]TFW}pfF`uYXZeik+a|fLC)Z%I814S]&' );
define( 'NONCE_SALT',       '3_}[@+9?V*hZ++saUBf!<WFs)]Kx#H6~#-.X.i)yo.bw}As)`1u6o>zMf,4K!2Ci' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
