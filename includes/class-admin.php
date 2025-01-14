<?php

class UP_GSAP_Admin {
    private $options;
    private $option_name = 'up_gsap_animate_options';
    private $page_title = 'UP GSAP Animate Settings';
    private $menu_title = 'UP GSAP Animate';
    private $capability = 'manage_options';
    private $menu_slug = 'up-gsap-animate';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_options_page'));
        add_action('admin_init', array($this, 'register_settings'));

        $this->options = get_option($this->option_name, array(
            'generate_js_files' => false
        ));
    }

    public function add_options_page() {
        add_options_page(
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            array($this, 'render_options_page')
        );
    }

    public function register_settings() {
        register_setting(
            $this->option_name,
            $this->option_name,
            array(
                'type' => 'array',
                'description' => 'Settings for UP GSAP Animate',
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => array(
                    'generate_js_files' => false
                )
            )
        );

        add_settings_section(
            'up_gsap_animate_main',
            'Main Settings',
            array($this, 'render_section_description'),
            $this->menu_slug
        );

        add_settings_field(
            'generate_js_files',
            'Generate JavaScript Files',
            array($this, 'render_generate_js_field'),
            $this->menu_slug,
            'up_gsap_animate_main'
        );
    }

    public function sanitize_options($input) {
        $sanitized = array();
        
        // Sanitize generate_js_files option
        $sanitized['generate_js_files'] = isset($input['generate_js_files']) 
            ? (bool) $input['generate_js_files'] 
            : false;

        return $sanitized;
    }

    public function render_section_description() {
        echo '<p>Configure the settings for UP GSAP Animate plugin.</p>';
    }

    public function render_generate_js_field() {
        $checked = !empty($this->options['generate_js_files']) ? 'checked' : '';
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr($this->option_name); ?>[generate_js_files]" 
                   value="1" 
                   <?php echo $checked; ?>>
            Generate separate JavaScript files for each page with animations
        </label>
        <p class="description">
            When enabled, the plugin will generate a JavaScript file in your theme's assets/js/gsap directory 
            each time you save a page containing animations. This can improve performance by reducing inline JavaScript.
        </p>
        <?php
    }

    public function render_options_page() {
        if (!current_user_can($this->capability)) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->menu_slug);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function get_option($key = null) {
        if ($key === null) {
            return $this->options;
        }
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }
}
