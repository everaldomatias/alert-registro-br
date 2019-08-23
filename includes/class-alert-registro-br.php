<?php
/**
 * Alert_Registro_Br
 *
 * @package Alert_Registro_Br/Classes
 * @version	0.0.1
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
                    'add_new' => __( 'Add new domain to alert', 'alert-registro-br' ),
                    'add_new_item' => __( 'Add new domain to alert', 'alert-registro-br' ),
                    'edit_item' => __( 'Domain to alert', 'alert-registro-br' )
                ),
                'public'      => true,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => 'options-general.php',
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => false,
                'show_in_rest' => true,
                'rest_base' => 'alerts',
                'menu_icon' => 'dashicons-warning',
                'supports' => array( 'title' ),
                'rewrite' => false,
                'query_var' => false
            )
        );
    }

    public function arb_add_custom_meta_boxes() {

        add_meta_box(
            'arb_mail_to_alert',
            esc_html__( 'Mail to alert', 'alert-registro-br' ),
            array( $this, 'arb_custom_html_meta_box' ),
            'domains-alerts',
            'normal',
            'high'
        );
    }

    public function arb_custom_html_meta_box( $post ) { ?>

        <?php wp_nonce_field( basename( __FILE__ ), 'arb_meta_box_nonce' ); ?>

        <p>
            <label for="arb-mail-to-alert"><?php _e( "Adicione o email para receber o alerta da expiração do domínio.", 'alert-registro-br' ); ?></label>
            <br>
            <input class="arb-mail-to-alert" type="email" name="arb-mail-to-alert" id="arb-mail-to-alert" placeholder="<?php _e( 'Email', 'alert-registro-br' ); ?>"value="<?php echo esc_attr( get_post_meta( $post->ID, 'arb_mail_to_alert', true ) ); ?>" required>
        </p>

    <?php }

    public function arb_custom_meta_boxes_setup() {

        /* Add meta boxes on the 'add_meta_boxes' hook. */
        add_action( 'add_meta_boxes', array( $this, 'arb_add_custom_meta_boxes' ) );

        /* Save post meta on the 'save_post' hook. */
        add_action( 'save_post', array( $this, 'arb_save_custom_meta_box' ), 10, 2 );

    }

    /* Save the meta box's post metadata. */
    public function arb_save_custom_meta_box( $post_id, $post ) {

        /* Verify the nonce before proceeding. */
        if ( ! isset( $_POST['arb_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['arb_meta_box_nonce'], basename( __FILE__ ) ) )
            return $post_id;

        /* Get the post type object. */
        $post_type = get_post_type_object( $post->post_type );

        /* Check if the current user has permission to edit the post. */
        if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
            return $post_id;

        /* Get the posted data and sanitize it for use as an HTML class. */
        $new_meta_value = ( isset( $_POST['arb-mail-to-alert'] ) ? sanitize_email( $_POST['arb-mail-to-alert'] ) : '' );

        /* Get the meta key. */
        $meta_key = 'arb_mail_to_alert';

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        /* If a new meta value was added and there was no previous value, add it. */
        if ( $new_meta_value && '' == $meta_value )
            add_post_meta( $post_id, $meta_key, $new_meta_value, true );

        /* If the new meta value does not match the old value, update it. */
        elseif ( $new_meta_value && $new_meta_value != $meta_value )
            update_post_meta( $post_id, $meta_key, $new_meta_value );

        /* If there is no new meta value but an old value exists, delete it. */
        elseif ( '' == $new_meta_value && $meta_value )
            delete_post_meta( $post_id, $meta_key, $meta_value );
    }

    public function arb_change_title_text( $title ) {
        $screen = get_current_screen();

        if  ( 'domains-alerts' == $screen->post_type ) {
            $title = __( 'Enter the domain name here', 'alert-registro-br' );
        }

        return $title;
    }

    public function arb_set_custom_edit_columns( $columns ) {

        unset( $columns['title'] );
        unset( $columns['date'] );
        
        return array_merge ( $columns, array ( 
            'title'             =>  __( 'Domain', 'alert-registro-br' ),
            'arb-mail-to-alert' => __( 'Email', 'alert-registro-br' ),
            'date'              => __( 'Date', 'alert-registro-br' )
        ) );

    }

    public function arb_custom_domains_alerts_column( $column, $post_id ) {
        switch ( $column ) {

            case 'arb-mail-to-alert' :
                $mail = get_post_meta( $post_id , 'arb_mail_to_alert', true );
                if ( is_string( $mail ) )
                    echo $mail;
                else
                    echo '--';
                break;

        }
    }

    /**
     * Add link to Settings on plugin action links
     */
    public function arb_add_plugin_action_links( $links ) {

        $links = array_merge( array(
            '<a href="' . esc_url( admin_url( '/edit.php?post_type=domains-alerts' ) ) . '">' . __( 'Settings', 'alert-registro-br' ) . '</a>'
        ), $links );

        return $links;

    }


}

new Alert_Registro_Br();