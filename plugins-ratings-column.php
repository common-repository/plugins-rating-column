<?php
/**
 * Plugin Name: Plugins Rating Column
 * Plugin URI: https://virtumente.com/wp/plugins/plugins-rating-column/
 * Description: This plugin adds a 'Rating' column to the admin plugins page.
 * Version: 0.1.0
 * Author: Virtumente
 * Author URI: https://virtumente.com/
 * License: GPLv2 or later
 */

if ( ! defined ( 'ABSPATH' ) ) {
    exit;
}

class Plugins_Rating
{

    public $cacheTime    = 1800;

    public $slugRating  = "plugins-rating ";

    function __construct ()
    {
        add_filter ( 'manage_plugins_columns', array ( $this, 'columnHeading' ) );
        add_filter ( 'manage_plugins-network_columns', array ( $this, 'columnHeading' ) );
        add_action ( 'manage_plugins_custom_column', array ( $this, 'columnData' ), 10, 3 );
        add_action ( 'admin_menu', array ( $this, 'menu' ) );
        add_action ( 'admin_notices', array ( $this, 'notices' ) );

        $this->firstColumnHeading = true;

    }
 
    function columnData ( $columnName, $pluginFile, $pluginData )
    {
        if ( $this->slugRating == $columnName ) {
            $this->columnRating ( $columnName, $pluginFile, $pluginData );
        } 
    }

    public function columnRating ( $columnName, $pluginFile, $pluginData )
    {
        $pluginDirectory = explode ( '/', $pluginFile );
        $Rating     = $this->getPluginsRating ( $pluginDirectory[ 0 ] );
        ?>
            <span><?php echo $Rating; ?></span>
        <?php
    }
 
    function getPluginsRating ( $pluginSlug )
    {
        if ( ! get_transient ( $this->slugRating . $pluginSlug ) ) {

            include_once ( ABSPATH . 'wp-admin/includes/plugin-install.php' );

            $call_api = @plugins_api (
                    'plugin_information',
                    array (
                            'slug'   => $pluginSlug,
                            'fields' => array ( 'rating' )
                    )
            );

            if ( is_wp_error ( $call_api ) ) {
                set_transient ( $this->slugRating . $pluginSlug, 0, $this->cacheTime );
                return -1;
            } else {
                if ( ! empty( $call_api->rating ) ) {
                    set_transient ( $this->slugRating . $pluginSlug, $call_api->rating,
                            $this->cacheTime );
                    return $call_api->rating;
                } else {
                    set_transient ( $this->slugRating . $pluginSlug, 0, $this->cacheTime );
                    return 0;
                }
            }
        } else {

            return get_transient ( $this->slugRating . $pluginSlug );
        }
    }

    function columnHeading ( $columns )
    {
        $columns[ $this->slugRating ]  = '<span>Rating</span>';
        return $columns;
    }

    public function menu ()
    {
        add_submenu_page ( 'plugins.php', 'Plugins Columns', 'Plugin Columns', 'manage_options', $this->slugSettings,
                array ( $this, 'settings' ) );
    } 

    public function notices ()
    {
        $screen = get_current_screen ();
        if ( isset( $screen ) and $screen->base === ( "plugins_page_" . $this->slugSettings ) and $_REQUEST[ 'clear-cache' ] == "true" ):
            global $wpdb;
            $wpdb->query ( "DELETE FROM `" . $wpdb->options . "` WHERE `option_name` LIKE ('%" . $this->slugRating . "%')" );
            ?>
            <div class="rating">
                <p>
                    Cache Cleared.
                </p>
            </div>
            <?php
        endif;
    }

    public function roundDown ( $num, $max )
    {
        if( $num === 0 )
            return $num;
        $remainder = ( $num % $max );
        if ( $remainder > 0 )
            return $remainder;
        return $num;
    }

    public function settings ()
    {
        $url = ( is_ssl () ? 'https://' : 'http://' ) . $_SERVER[ "HTTP_HOST" ] . $_SERVER[ "REQUEST_URI" ];
        ?>
        <div class="wrap">
            <h1>Clear Plugins Ratings Cache</h1>
            <p>
                <a href="<?= $url; ?>&clear-cache=true">Clear Plugins Ratings Cache</a>
            </p>
        </div>
        <?php
    }
}

$Plugins_Rating = new Plugins_Rating();
?>
