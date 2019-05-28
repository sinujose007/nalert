<?php

class Nalert {
	
	/**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
	
	public function register()
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' )  );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueuefront' )  );
		add_filter( 'the_content', array( $this, 'content_alertbox' ) );
	}
	
	/**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Nalert Settings', 
            'manage_options', 
            'my-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }
	
	/**
     * Options page callback
     */
    public function create_admin_page()
    {
        $this->options = get_option( 'nalert_option_name' );
        ?>
        <div class="wrap">
            <h1>Nalert Settings</h1>
            <form method="post" action="options.php" onsubmit="return validate_nalert();" >
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'nalert_option_group' );
                do_settings_sections( 'my-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }
	
	/**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'nalert_option_group', // Option group
            'nalert_option_name',
			 array ( $this, 'sanitize' )
        );

        add_settings_section(
            'setting_section_id', // ID
            'Nalert Custom Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'my-setting-admin' // Page
        );             

        add_settings_field(
            'title', 
            'Alert Text *', 
            array( $this, 'title_callback' ), 
            'my-setting-admin', 
            'setting_section_id'
        );
		
		add_settings_field(
            'nposts', 
            'Select Post Types *',  
            array( $this, 'get_nposts_callback' ), 
            'my-setting-admin', 
            'setting_section_id'           
        ); 
		
    }
	
	/**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );
		
		if( isset( $input['nposts'] ) ){
			foreach( $input['nposts'] as $k => $v ){
				if( !is_array( $v ) && !empty ( $input['nposts'][$v] ) ){
					$new_input['nposts'][$k] = $v;
					$new_input['nposts'][$v] = $input['nposts'][$v];				
				}
			}
		}
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function get_nposts_callback()
    {
        $args = array(
			'public'   => true,
			'_builtin' => false
		);

		$output = 'names'; 
		$operator = 'and'; 

		$post_types = get_post_types( $args, $output, $operator ); 
		if ( empty( $post_types ) ){
			echo "<span class='nalert_error'>Custom Post Types Are Not Found, Please Create Some.</span>" ;
		}
		else {
			$saved_nalerts = $this->options['nposts'];
			
			foreach ( $post_types  as $post_type ) {
				$check = '';
				$display = 'display:none';
				if ( is_array ( $saved_nalerts ) ) {
					if ( in_array( $post_type, $saved_nalerts ) ){
						$check = 'checked';
						$display = '';
					}
				}			
				echo '<div class="nalert-block">';
				echo '<span class="nalert_each"><input class="nalert_post_meta" '. $check .' type="checkbox" name="nalert_option_name[nposts][]" value="'.$post_type.'">'.$post_type.'</span>';
				$posts = array();
				$posts = get_posts([
					'post_type' => $post_type, 'post_status' => 'publish', 'numberposts' => -1, 'order'    => 'ASC'
				]);
				if ( !empty( $posts ) ) {
					echo '<div id="check_'. $post_type. '" style="'. $display .'" class="nalert_hdb"><p class="nalert_post_selection">Select The posts</p>';
					foreach ( $posts as $k => $v ){
						$checksub = '';
						$post_t = $v->post_title;
						$post_v = $v->ID;
						if( isset( $saved_nalerts[$post_type] ) ) 
						$checksub = ( in_array( $post_v, $saved_nalerts[$post_type] ) ? 'checked' : '' ); 
						echo '<span class="nalert_each_opt"><input '. $checksub .' type="checkbox" name="nalert_option_name[nposts]['.$post_type.'][]" value="'.$post_v.'">'.$post_t.'</span><br/>';
					}
					echo '</div>';
				}
				echo '</div>';
			}
		}
		
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function title_callback()
    {
        printf(
            '<input type="text" id="title" required="required" name="nalert_option_name[title]" value="%s" />',
            isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
        );
    }
	
	/** 
     * Filter the custom post type and display alert box based on admin settings.
     */
	function content_alertbox( $content ) 
	{
		if ( is_single() ) {
			$title = '';
			$this->options = get_option( 'nalert_option_name' );
			if ( is_array( $this->options['nposts'] ) && !empty ( $this->options['nposts'] ) && $this->options['title'] != '' ) {
				$title = $this->options['title'];
				$current_post_type = get_post_type();
				if ( in_array( $current_post_type, $this->options['nposts'] ) ){ 
					if( in_array( get_the_ID(), $this->options['nposts'][$current_post_type] ) ){
						$custom_content = '<p style="display:none;" id="extraPluginAlert">'.$title.'</p>';
						$content .= $custom_content;
					}
				}
				return $content;
			}				
		}
	}

	/** 
     * Plugin activation code
     */
	public static function activate()
	{
		flush_rewrite_rules();
	}
	
	/** 
     * Plugin deactivation 
     */
	public static function deactivate()
	{
		flush_rewrite_rules();
	}
	
	/** 
     * Load admin end script and styles
     */
	public static function enqueue()
	{
		//enqueue all our scripts
		wp_register_style( 'nstyle.css', plugin_dir_url( __FILE__ ) . 'assets/nstyle.css', array(), NALERT_VERSION, 'all');
		wp_enqueue_style( 'nstyle.css');
		
		wp_register_script( 'nalert.js', plugin_dir_url( __FILE__ ) . 'assets/nalert.js', array('jquery'), NALERT_VERSION );
		wp_enqueue_script( 'nalert.js' );
	}
	
	/** 
     * Load front end script and styles
     */
	public static function enqueuefront()
	{
		//enqueue all our scripts
		wp_register_style( 'notify.css', plugin_dir_url( __FILE__ ) . 'assets/notify.css', array(), NALERT_VERSION, 'all');
		wp_enqueue_style( 'notify.css');
		
		wp_register_script( 'nalert.js', plugin_dir_url( __FILE__ ) . 'assets/nalert.js', array('jquery'), NALERT_VERSION );
		wp_register_script( 'notify.js', plugin_dir_url( __FILE__ ) . 'assets/notify.js', array('jquery'), NALERT_VERSION );		
		wp_enqueue_script( 'nalert.js' );
		wp_enqueue_script( 'notify.js' );
	}
	
	
}

	 