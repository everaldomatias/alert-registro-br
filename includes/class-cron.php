<?php
/**
 * Alert_Registro_Br_Cron
 *
 * @package Alert_Registro_Br/Classes
 * @version	0.0.1
 * @since 0.0.3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Alert_Registro_Br_Cron {

	/**
	 * Setup the plugin
	 */
	public function __construct() {

		if ( is_admin() ) {
            /** 
             * Enqueue custom CSS on admin area
             */
			//add_action( 'admin_head', array( $this, 'arb_admin_css' ), 50 );
        }

        do_action( 'arb_cron_hook' );

        /**
         * Create new cron interval (temporarily for development)
         */
        add_filter( 'cron_schedules', array( $this, 'arb_add_cron_interval' ) );
        
        add_action( 'arb_cron_hook', array( $this, 'arb_cron_exec' ) );

    }

    static function arb_activation() {
        
        $timestamp = wp_next_scheduled( 'arb_cron_hook' );

        if( $timestamp == false ){
            wp_schedule_event( time(), 'one_minute', 'arb_cron_hook' );
        }

    }

    static function arb_deactivation() {

        wp_clear_scheduled_hook( 'arb_cron_hook' );

    }
    
    public function arb_get_curl( $url ) {
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, True );
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl,CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1' );
        $return = curl_exec( $curl );
        curl_close( $curl );
        return $return;
    }

    public function arb_cron_exec() {
        $post_id = -1;
        $title = 'My Example Post -> ' . date("Y-m-d H:i:s");
        $curlsss = wp_remote_get( "https://rdap.registro.br/domain/agpagencia.com.br" );
        
        // If the page doesn't already exist, then create it
        if( null == get_page_by_title( $title ) ) {
            $post_id = wp_insert_post(
                array(
                    'comment_status'	=>	'closed',
                    'ping_status'		=>	'closed',
                    'post_title'		=>	$title,
                    'post_status'		=>	'publish',
                    'post_content'      =>  $curlsss,
                    'post_type'		    =>	'post'
                )
            );
        } else {
            $post_id = -2;
        }

    }


    public function arb_add_cron_interval( $schedules ) {
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display' => esc_html__( 'Every Sixty Seconds' ),
        );

        return $schedules;
    }
    
}
new Alert_Registro_Br_Cron();