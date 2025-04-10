<?php
/*
 * Plugin Name: UsersWP Custom Fields Display
 * Description: Affiche les champs personnalisés de UsersWP dans la liste des utilisateurs de l'admin WordPress avec une page de réglages pour choisir les champs.
 * Version: 1.13
 * Author: Pikkendorff & Grok
 * License: GPL-2.0+
 * Text Domain: uwp-custom-fields-display
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class UsersWPCustomFieldsDisplay {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_filter('manage_users_columns', array($this, 'add_custom_columns'));
        add_filter('manage_users_custom_column', array($this, 'display_custom_column_value'), 10, 3);
        add_filter('manage_users_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_user_query', array($this, 'sort_by_custom_field'));
        
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('uwp-custom-fields-display', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function add_settings_page() {
        add_submenu_page(
            'userswp',
            __('Custom Fields Display', 'uwp-custom-fields-display'),
            __('Custom Fields Display', 'uwp-custom-fields-display'),
            'manage_options',
            'uwp-custom-fields-display',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('uwp_custom_fields_display_group', 'uwp_users_tab_fields', array(
            'sanitize_callback' => array($this, 'sanitize_fields')
        ));
    }

    public function sanitize_fields($input) {
        return is_array($input) ? array_map('sanitize_text_field', $input) : array();
    }

    public function render_settings_page() {
        $fields = $this->get_all_userswp_fields();
        $selected_fields = get_option('uwp_users_tab_fields', array());
        $donate_url = 'https://www.paypal.com/donate/?hosted_button_id=EFBK5UT6BX8JG';
        ?>
        <div class="wrap">
            <h1><?php _e('UsersWP Custom Fields Display Settings', 'uwp-custom-fields-display'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('uwp_custom_fields_display_group'); ?>
                <?php do_settings_sections('uwp_custom_fields_display_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Fields to Display in Users Tab', 'uwp-custom-fields-display'); ?></th>
                        <td>
                            <?php if (!empty($fields)) : ?>
                                <?php foreach ($fields as $key => $field_data) : ?>
                                    <label>
                                        <input type="checkbox" name="uwp_users_tab_fields[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected_fields)); ?>>
                                        <?php echo esc_html($field_data['label']); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php _e('No custom fields found.', 'uwp-custom-fields-display'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <?php submit_button(); ?>
                    <a href="<?php echo esc_url($donate_url); ?>" class="button" target="_blank"><?php _e('Donate to the Developer', 'uwp-custom-fields-display'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }

    private function get_all_userswp_fields() {
        global $wpdb;
        $table_name = uwp_get_table_prefix() . 'uwp_form_fields';
        
        $fields = $wpdb->get_results(
            "SELECT htmlvar_name, site_title, field_type 
             FROM $table_name 
             WHERE form_type = 'account' 
             AND css_class NOT LIKE '%uwp_social%' 
             AND htmlvar_name IS NOT NULL 
             AND htmlvar_name != ''"
        );
        
        $custom_fields = array();
        if ($fields) {
            foreach ($fields as $field) {
                $custom_fields[$field->htmlvar_name] = array(
                    'label' => stripslashes($field->site_title ?: $field->htmlvar_name),
                    'type' => $field->field_type
                );
            }
        }
        return $custom_fields;
    }

    private function get_userswp_fields() {
        $all_fields = $this->get_all_userswp_fields();
        $selected_fields = get_option('uwp_users_tab_fields', array());
        return array_intersect_key($all_fields, array_flip($selected_fields));
    }

    public function add_custom_columns($columns) {
        $custom_fields = $this->get_userswp_fields();
        foreach ($custom_fields as $field_key => $field_data) {
            $columns[$field_key] = $field_data['label'];
        }
        return $columns;
    }

    public function display_custom_column_value($value, $column_name, $user_id) {
        global $wpdb;
        $custom_fields = $this->get_userswp_fields();
        if (array_key_exists($column_name, $custom_fields)) {
            $field_type = $custom_fields[$column_name]['type'];
            
            $meta_value = $wpdb->get_var($wpdb->prepare(
                "SELECT `$column_name` FROM " . uwp_get_table_prefix() . "uwp_usermeta WHERE user_id = %d",
                $user_id
            ));
            
            if ($meta_value !== null && $meta_value !== '') {
                switch ($field_type) {
                    case 'datepicker':
                        return date_i18n('d/m/Y', strtotime($meta_value));
                    case 'checkbox':
                        return $meta_value == '1' ? __('Yes', 'uwp-custom-fields-display') : __('No', 'uwp-custom-fields-display');
                    case 'select':
                    case 'multiselect':
                        return is_array($meta_value) ? esc_html(implode(', ', $meta_value)) : esc_html($meta_value);
                    default:
                        return esc_html($meta_value);
                }
            }
            return '—';
        }
        return $value;
    }

    public function make_columns_sortable($columns) {
        $custom_fields = $this->get_userswp_fields();
        foreach ($custom_fields as $field_key => $field_data) {
            $columns[$field_key] = $field_key;
        }
        return $columns;
    }

    public function sort_by_custom_field($query) {
        global $wpdb;
        if (!is_admin()) {
            return;
        }

        $orderby = isset($query->query_vars['orderby']) ? $query->query_vars['orderby'] : '';
        $order = isset($query->query_vars['order']) ? strtoupper($query->query_vars['order']) : 'ASC';
        $custom_fields = $this->get_userswp_fields();

        if (array_key_exists($orderby, $custom_fields)) {
            $table_name = uwp_get_table_prefix() . 'uwp_usermeta';
            $query->query_from .= " LEFT JOIN $table_name AS uwp_meta ON $wpdb->users.ID = uwp_meta.user_id";
            $query->query_orderby = " ORDER BY uwp_meta.`$orderby` $order, $wpdb->users.ID $order";
        }
    }
}

new UsersWPCustomFieldsDisplay();