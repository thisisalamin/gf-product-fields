<?php
/**
 * Plugin Name: Gravity Forms Product Fields Enhancer
 * Description: Enhances existing Gravity Forms product fields with multi-select and quantity options
 * Version: 1.0
 * Author: Mohamed Alamin
 * Author URI: https://crafely.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gfproductfields
 * Domain Path: /languages
 * Requires PHP: 5.6
 * Requires at least: 5.2
 * Tested up to: 5.8
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

class GF_Enhanced_Product_Fields {
    
    private $nonce_key = 'gf_product_fields_nonce';
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('gform_field_standard_settings', array($this, 'add_custom_product_field_options'), 10, 2);
        add_filter('gform_admin_pre_render', array($this, 'modify_product_field_admin'));
        add_filter('gform_field_input', array($this, 'modify_product_field_choices'), 10, 5);
    }

    // Enqueue frontend JavaScript
    public function enqueue_scripts() {
        wp_enqueue_script('gf-enhanced-product-fields', plugin_dir_url(__FILE__) . 'assets/custom.js', array('jquery'), '1.7', true);
    }

    // Enqueue admin scripts to prevent conflicts
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'toplevel_page_gf_edit_forms') {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tabs');
        }
    }

    // Add Multi-Select & Checkbox as options in Product Fields inside existing Field Type dropdown
    public function add_custom_product_field_options($position, $form_id) {
        if ($position == 50) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                var selectField = $("#product_field_type");
                if (selectField.length) {
                    if (!selectField.find("option[value='multiselect']").length) {
                        selectField.append('<option value="multiselect"><?php echo esc_js(__("Multi-Select Dropdown", "gfproductfields")); ?></option>');
                        selectField.append('<option value="checkbox"><?php echo esc_js(__("Checkboxes", "gfproductfields")); ?></option>');
                    }
                }
                selectField.on('change', function() {
                    if (typeof SetFieldProperty === 'function') {
                        SetFieldProperty('inputType', $(this).val());
                    }
                });
            });
            </script>
            <?php
        }
    }

    // Modify product field in form editor to support multi-select & checkbox types
    public function modify_product_field_admin($form) {
        foreach ($form['fields'] as &$field) {
            if ($field->type == 'product') {
                if ($field->inputType == 'multiselect') {
                    $field->enableMultiple = true;
                } elseif ($field->inputType == 'checkbox') {
                    $field->enableMultiple = true;
                }
            }
        }
        return $form;
    }

    // Modify the frontend product field to render multi-select & checkboxes correctly
    public function modify_product_field_choices($input, $field, $value, $lead_id, $form_id) {
        if (!$field || $field->type !== 'product') {
            return $input;
        }

        if ($field->inputType === 'multiselect') {
            $input = str_replace('<select', '<select multiple', $input);
        } elseif ($field->inputType === 'checkbox' && !empty($field->choices)) {
            $choices_html = '';
            foreach ($field->choices as $choice) {
                if (!isset($choice['value']) || !isset($choice['text'])) {
                    continue;
                }
                $choices_html .= sprintf(
                    '<label><input type="checkbox" name="input_%d[]" value="%s"> %s</label><br>',
                    absint($field->id),
                    esc_attr($choice['value']),
                    esc_html($choice['text'])
                );
            }
            $input = sprintf('<div class="gf-product-checkbox-group">%s</div>', $choices_html);
        }
        
        return $input;
    }
}

// Initialize the class
add_action('plugins_loaded', function() {
    if (class_exists('GFCommon')) {
        new GF_Enhanced_Product_Fields();
    }
});

// Move JavaScript to a separate file and enqueue properly
remove_action('wp_footer', 'custom_footer_script');
