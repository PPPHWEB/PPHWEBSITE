<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u874569194_gyNVp' );

/** Database username */
define( 'DB_USER', 'u874569194_EKsCV' );

/** Database password */
define( 'DB_PASSWORD', '\\g|d3*tC&7' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

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
define( 'AUTH_KEY',          'HF0}k{EKc>0QSp@%$g+CmCg;s&)jf1cwNw;5rm.6N~,e*z9H-?JyOSajPtn&zUa6' );
define( 'SECURE_AUTH_KEY',   ':jc}a>q^2L5-mZsCw%KSPv{zy@0#~U2`[jNlu95F;gOvWRLEy,m!t%K6@T;!!gui' );
define( 'LOGGED_IN_KEY',     'FL&9m2Ls#tZZ69y,ms*OB<$x`![7j>*/,kA@iO~WWnI`]P79HhhB^>0R~3Oa]-S&' );
define( 'NONCE_KEY',         'nD[>-,ATBL=8uXE&<F?_eDY;+R4m%q]R@piS]2oN)^Elz3YVEXw<SgiDT!,ob{6F' );
define( 'AUTH_SALT',         '?r}Uf<^@QVUwmy,dQL.Q8;TFe9ZU{n/7.R9)-jj0gJi M.g}<H#Bh*}wPY%}FN-Y' );
define( 'SECURE_AUTH_SALT',  'w2JvWVt4aM0KoDQN=RY*mCZ[_vnL*[-|o]h>&x{ va2,Wv1f+xL>$R-=X]{mVUW:' );
define( 'LOGGED_IN_SALT',    '`_z/3BjqAN;INqE/p=3hpoB@RgLO9>Zxx7@L>:`(]sMDN3X6?wfFH32ssO-Z=X9W' );
define( 'NONCE_SALT',        'DKMOp@/_#^]DjtlJa`/`8t/_1B5|>(09~ZtF|=k]s8t(DVxqix<|,K{Mj:&GC%xM' );
define( 'WP_CACHE_KEY_SALT', '5I$C@lWO3SD!cdSuI{|<x>CB>aFhv,`nEL2WLwj|/v[1yGO gQuVfd!OpJuc3v/Q' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'b4c7379220512977a5be3d880dc818c3' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
