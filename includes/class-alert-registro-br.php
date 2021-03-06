<?php
/**
 * Alert_Registro_Br
 *
 * @package Alert_Registro_Br/Classes
 * @version	0.0.2
 * @since 0.0.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Alert_Registro_Br {

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
        
        /** 
         * Enqueue the Admin CSS
         */
        add_action( 'wp_enqueue_scripts', array( $this, 'arb_css' ), 50 );
        
        /**
         * Register the Custom Post Type
         */
        add_action( 'init', array( $this, 'arb_custom_post_type' ), 50 );

        /**
         * 
         */
        add_action( 'load-post.php', array( $this, 'arb_custom_meta_boxes_setup' ) );
        add_action( 'load-post-new.php', array( $this, 'arb_custom_meta_boxes_setup' ) );
        add_action( 'save_post', array( $this, 'arb_save_custom_meta_box' ), 10, 2 );
        add_filter( 'enter_title_here', array( $this, 'arb_change_title_text' ) );

        add_filter( 'manage_domains-alerts_posts_columns', array( $this, 'arb_set_custom_edit_columns' ) );
        add_action( 'manage_domains-alerts_posts_custom_column' , array( $this, 'arb_custom_domains_alerts_column' ), 10, 2 );
        add_action( 'plugin_action_links_' . plugin_basename( ARB_FILE ), array( $this, 'arb_add_plugin_action_links' ), 10, 5 );
        
        add_action( 'save_post', array( $this, 'arb_get_domain_expiration' ), 99, 3 );

    }
    
    /**
	 * Enqueue the Admin CSS.
	 *
	 * @return void
	 */
	public function arb_admin_css() {
		wp_enqueue_style( 'arb-admin-css', plugins_url( '/assets/css/style-admin.css', ARB_FILE ) );
	}

	/**
	 * Enqueue the CSS on frontend.
	 *
	 * @return void
	 */
	public function arb_css() {
		wp_enqueue_style( 'arb-css', plugins_url( '/assets/css/style.css', ARB_FILE ) );
    }

    /**
     * Register the Custom Post Type
     */
    public function arb_custom_post_type() {
        register_post_type( 'domains-alerts',
            array(
                'labels'    => array(
                    'name'          => __( 'Domains Alerts', 'alert-registro-br' ),
                    'singular_name' => __( 'Alert', 'alert-registro-br' ),
                    'add_new'       => __( 'Add new domain to alert', 'alert-registro-br' ),
                    'add_new_item'  => __( 'Add new domain to alert', 'alert-registro-br' ),
                    'edit_item'     => __( 'Domain to alert', 'alert-registro-br' )
                ),
                'public'      => true,
                'has_archive' => false,
                'exclude_from_search'   => true,
                'publicly_queryable'    => false,
                'show_ui'               => true,
                'show_in_menu'          => 'options-general.php',
                'show_in_nav_menus'     => false,
                'show_in_admin_bar'     => false,
                'show_in_rest'          => true,
                'rest_base'             => 'alerts',
                'menu_icon'             => 'dashicons-warning',
                'supports'              => array( 'title' ),
                'rewrite'               => false,
                'query_var'             => false
            )
        );
    }

    /**
     * Register the custom metabox
     */
    public function arb_add_custom_meta_boxes() {

        add_meta_box(
            'arb_mail_to_alert_mb',
            esc_html__( 'Settings to alert', 'alert-registro-br' ),
            array( $this, 'arb_custom_html_meta_box' ),
            'domains-alerts',
            'normal',
            'high'
        );
    }

    public function arb_custom_html_meta_box( $post ) { ?>

        <?php wp_nonce_field( basename( __FILE__ ), 'arb_meta_box_nonce' ); ?>

        <p>
            <label for="arb-domain-to-alert"><?php _e( "Adicione o domínio para acompanhar a expiração do domínio.", 'alert-registro-br' ); ?></label>
            <br>
            <input class="arb-domain-to-alert" type="text" name="arb-domain-to-alert" id="arb-domain-to-alert" placeholder="<?php _e( 'Domain', 'alert-registro-br' ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, 'arb_domain_to_alert', true ) ); ?>" required>
        </p>

        <p>
            <label for="arb-mail-to-alert"><?php _e( "Adicione o email para receber o alerta da expiração do domínio.", 'alert-registro-br' ); ?></label>
            <br>
            <input class="arb-mail-to-alert" type="email" name="arb-mail-to-alert" id="arb-mail-to-alert" placeholder="<?php _e( 'Email', 'alert-registro-br' ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, 'arb_mail_to_alert', true ) ); ?>" required>
        </p>
        
        <p>
            <label for="arb-domain-expiration"><?php _e( "Esse campo será preenchido automaticamente.", 'alert-registro-br' ); ?></label>
            <br>
            <input class="arb-domain-expiration" type="text" name="arb-domain-expiration" id="arb-domain-expiration" placeholder="<?php _e( 'Expiration date', 'alert-registro-br' ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, 'arb_domain_expiration', true ) ); ?>" disabled>
        </p>

    <?php }

    public function arb_custom_meta_boxes_setup() {

        /* Add meta boxes on the 'add_meta_boxes' hook. */
        add_action( 'add_meta_boxes', array( $this, 'arb_add_custom_meta_boxes' ) );

        /* Save post meta on the 'save_post' hook. */
        add_action( 'save_post', array( $this, 'arb_save_custom_meta_box' ), 10, 2 );

    }

    /**
     * Save the values from custom metabox
     */
    public function arb_save_custom_meta_box( $post_id, $post ) {

        /* Verify the nonce before proceeding. */
        if ( ! isset( $_POST['arb_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['arb_meta_box_nonce'], basename( __FILE__ ) ) )
            return $post_id;

        /* Get the post type object. */
        $post_type = get_post_type_object( $post->post_type );

        /* Check if the current user has permission to edit the post. */
        if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
            return $post_id;

        // Domain
        $new_meta_value_domain = ( isset( $_POST['arb-domain-to-alert'] ) ? sanitize_text_field( $_POST['arb-domain-to-alert'] ) : '' );

        $meta_key_domain = 'arb_domain_to_alert';

        $meta_value_domain = get_post_meta( $post_id, $meta_key_domain, true );

        if ( $new_meta_value_domain && '' == $meta_value_domain )
            add_post_meta( $post_id, $meta_key_domain, $new_meta_value_domain, true );

        elseif ( $new_meta_value_domain && $new_meta_value_domain != $meta_value_domain )
            update_post_meta( $post_id, $meta_key_domain, $new_meta_value_domain );

        elseif ( '' == $new_meta_value_domain && $meta_value_domain )
            delete_post_meta( $post_id, $meta_key_domain, $meta_value_domain );

        // Email
        $new_meta_value_mail = ( isset( $_POST['arb-mail-to-alert'] ) ? sanitize_email( $_POST['arb-mail-to-alert'] ) : '' );

        $meta_key_mail = 'arb_mail_to_alert';

        $meta_value_mail = get_post_meta( $post_id, $meta_key_mail, true );

        if ( $new_meta_value_mail && '' == $meta_value_mail )
            add_post_meta( $post_id, $meta_key_mail, $new_meta_value_mail, true );

        elseif ( $new_meta_value_mail && $new_meta_value_mail != $meta_value_mail )
            update_post_meta( $post_id, $meta_key_mail, $new_meta_value_mail );

        elseif ( '' == $new_meta_value_mail && $meta_value_mail )
            delete_post_meta( $post_id, $meta_key_mail, $meta_value_mail );
            
        // Expiration date
        $new_meta_value_expiration = ( isset( $_POST['arb-domain-expiration'] ) ? sanitize_text_field( $_POST['arb-domain-expiration'] ) : '' );

        $meta_key_expiration = 'arb_domain_expiration';

        $meta_value_expiration = get_post_meta( $post_id, $meta_key_expiration, true );

        if ( $new_meta_value_expiration && '' == $meta_value_expiration )
            add_post_meta( $post_id, $meta_key_mail, $new_meta_value_expiration, true );

        elseif ( $new_meta_value_expiration && $new_meta_value_expiration != $meta_value_expiration )
            update_post_meta( $post_id, $meta_key_expiration, $new_meta_value_expiration );

        elseif ( '' == $new_meta_value_expiration && $meta_value_expiration )
            delete_post_meta( $post_id, $meta_key_expiration, $meta_value_expiration );
        
    }
    
    public function arb_get_curl( $url ) {
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, True );
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1' );
        $return = curl_exec( $curl );
        curl_close( $curl );
        return $return;
    }
    
    /**
     * Get and save date expiration
     */
    public function arb_get_domain_expiration( $post_id, $post, $update ) {
    
        $post_type = get_post_type( $post_id );
    
        if ( 'domains-alerts' != $post_type ) return;
        
        $arb_domain_to_alert = get_post_meta( $post_id, 'arb_domain_to_alert', true );
        $arb_domain_to_alert = preg_replace( '#^https?://#', '', rtrim( $arb_domain_to_alert, '/' ) );
        
        $infos = $this->arb_get_curl( 'https://rdap.registro.br/domain/' . $arb_domain_to_alert );
        $infos_json = json_decode( $infos );
        
        /** 
         * Save the expiration date on custom metabox
         */
        if ( $infos_json ) {
            $date = new DateTime( $infos_json->{'events'}[2]->eventDate, new DateTimeZone( 'America/Sao_Paulo' ) );
            $date_expiration = $date->format( 'Y-m-d' );
            update_post_meta( $post_id, 'arb_domain_expiration', esc_attr( $date_expiration ) );
        }
    
    }

    /**
     * Change the title for domain alerts
     */
    public function arb_change_title_text( $title ) {
        $screen = get_current_screen();

        if  ( 'domains-alerts' == $screen->post_type ) {
            $title = __( 'Enter the domain name here', 'alert-registro-br' );
        }

        return $title;
    }

    /**
     * Custom the columns for domain alerts
     */
    public function arb_set_custom_edit_columns( $columns ) {

        unset( $columns['title'] );
        unset( $columns['date'] );
        
        return array_merge ( $columns, array ( 
            'title'                 => __( 'Name', 'alert-registro-br' ),
            'arb-domain-to-alert'   => __( 'Domain', 'alert-registro-br' ),
            'arb-mail-to-alert'     => __( 'Email', 'alert-registro-br' ),
            'arb-domain-expiration' => __( 'Expiration', 'alert-registro-br' ),
            'date'                  => __( 'Create on', 'alert-registro-br' )
        ) );

    }

    public function arb_custom_domains_alerts_column( $column, $post_id ) {
        switch ( $column ) {

            case 'arb-domain-to-alert' :
                $domain = get_post_meta( $post_id , 'arb_domain_to_alert', true );
                if ( is_string( $domain ) )
                    echo $domain;
                else
                    echo '--';
                break;

            case 'arb-mail-to-alert' :
                $mail = get_post_meta( $post_id , 'arb_mail_to_alert', true );
                if ( is_string( $mail ) )
                    echo $mail;
                else
                    echo '--';
                break;
                
            case 'arb-domain-expiration' :
                $expiration = get_post_meta( $post_id , 'arb_domain_expiration', true );
                $today = new DateTime();
                                 
                $date_expiration = str_replace ( '/', '-', $expiration );
                $date_expiration = date( "Y-m-d", strtotime( $date_expiration ) );                
                $date_expiration = new DateTime( $date_expiration );
                $date_interval = $today->diff( $date_expiration );
                $date_interval = $date_interval->days;
                
                if ( ! empty( $expiration ) )
                    if ( $date_interval <= 15 ) {
                        echo '<span class="default-alert low-alert">' . esc_html( $expiration ) . '</span>';
                    } elseif( $date_interval <= 10 ) {
                        echo '<span class="default-alert medium-alert">' . esc_html( $expiration ) . '</span>';
                    } elseif( $date_interval <= 5 ) {
                        echo '<span class="default-alert high-alert">' . esc_html( $expiration ) . '</span>';
                    } else {
                        echo '<span class="default-alert">' . esc_html( $expiration ) . '</span>';
                    }
                else
                    echo '--';
                break;

        }
    }

    /**
     * Add link to settings page on plugin action links
     */
    public function arb_add_plugin_action_links( $links ) {

        $links = array_merge( array(
            '<a href="' . esc_url( admin_url( '/edit.php?post_type=domains-alerts' ) ) . '">' . __( 'Settings', 'alert-registro-br' ) . '</a>'
        ), $links );

        return $links;

    }


}

new Alert_Registro_Br();

add_filter( 'manage_edit-domains-alerts_sortable_columns', 'my_sortable_cake_column' );
function my_sortable_cake_column( $columns ) {
    $columns['arb-domain-expiration'] = 'arb-domain-expiration';
 
    return $columns;
}

add_action( 'pre_get_posts', 'my_slice_orderby' );
function my_slice_orderby( $query ) {
    if( ! is_admin() )
        return;
 
    $orderby = $query->get( 'orderby' );
 
    if( 'arb-domain-expiration' == $orderby ) {
        $query->set('meta_key','arb_domain_expiration');
        //$query->set('orderby','meta_value_num');
    }
}