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
        add_filter('gform_field_standard_settings', array($this, 'add_field_settings'), 10, 2);
        add_action('gform_editor_js', array($this, 'editor_script'));
        add_filter('gform_field_content', array($this, 'modify_field_content'), 10, 5);
        add_filter('gform_pre_render', array($this, 'pre_render_form'));
        add_filter('gform_form_post_get_meta', array($this, 'modify_form_meta'));
    }

    // Enqueue frontend JavaScript
    public function enqueue_scripts() {
        wp_enqueue_style(
            'gf-product-fields',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'gf-product-fields',
            plugin_dir_url(__FILE__) . 'assets/js/frontend.js',
            array('jquery', 'gform_gravityforms'),
            '1.0.0',
            true
        );
    }

    // Enqueue admin scripts to prevent conflicts
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'toplevel_page_gf_edit_forms') {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tabs');
        }
    }

    // Add Multi-Select & Checkbox as options in Product Fields inside existing Field Type dropdown
    public function add_field_settings($position, $form_id) {
        if ($position == 25) {
            ?>
            <li class="quantity_enabled_setting field_setting">
                <input type="checkbox" id="field_quantity_enabled" 
                       onclick="SetFieldProperty('quantityEnabled', this.checked);" />
                <label for="field_quantity_enabled" class="inline">
                    <?php esc_html_e('Enable Quantity Field', 'gfproductfields'); ?>
                </label>
            </li>
            <?php
        } elseif ($position == 50) {
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
                });
            </script>
            <?php
        }
    }

    public function editor_script() {
        ?>
        <script type="text/javascript">
            fieldSettings.product += ', .quantity_enabled_setting';
            
            // Add price column to choices
            gform.addFilter('gform_choices_edit_config', function(config, field) {
                if (field.type === 'product') {
                    config.columns.price = {
                        label: '<?php echo esc_js(__('Price', 'gfproductfields')); ?>',
                        required: true
                    };
                }
                return config;
            });

            // Handle choice price saving
            gform.addFilter('gform_choice_pre_save', function(choice, field) {
                if (field.type === 'product') {
                    if (!choice.price) {
                        choice.price = '0';
                    }
                }
                return choice;
            });

            jQuery(document).on('gform_load_field_settings', function(event, field, form) {
                jQuery('#field_quantity_enabled').prop('checked', field.quantityEnabled == true);
                
                // Initialize price column for existing choices
                if (field.type === 'product' && field.choices) {
                    field.choices.forEach(function(choice) {
                        if (!choice.price) {
                            choice.price = '0';
                        }
                    });
                }
            });

            // Handle product field type change
            jQuery(document).on('change', '#product_field_type', function() {
                var field = GetSelectedField();
                if (field && field.type === 'product') {
                    field.enablePrice = true;
                    
                    if (this.value === 'multiselect' || this.value === 'checkbox') {
                        field.enableMultiple = true;
                        field.inputType = this.value;
                        
                        // Initialize choices if empty
                        if (!field.choices) {
                            field.choices = [
                                { text: 'First Choice', value: 'first', price: '0' },
                                { text: 'Second Choice', value: 'second', price: '0' }
                            ];
                        }
                    }
                    
                    // Ensure all choices have price
                    if (field.choices) {
                        field.choices.forEach(function(choice) {
                            if (!choice.price) {
                                choice.price = '0';
                            }
                        });
                    }
                    
                    // Force refresh the field
                    SetFieldProperty('inputType', this.value);
                    RefreshSelectedFieldPreview();
                }
            });
        </script>
        <?php
    }

    // Modify product field in form editor to support multi-select & checkbox types
    public function modify_field_content($content, $field, $value, $entry_id, $form_id) {
        if ($field->type !== 'product') {
            return $content;
        }

        $field_id = $field->id;
        $input_type = rgar($field, 'inputType');
        
        if ($input_type === 'multiselect' || $input_type === 'checkbox') {
            $has_quantity = rgar($field, 'quantityEnabled', false);
            $choices = rgar($field, 'choices', array());
            
            // Add unique identifier for multiple fields
            $field->uniqueId = 'field_' . $field_id . '_' . $input_type;
            
            ob_start();
            include plugin_dir_path(__FILE__) . 'templates/field-' . $input_type . '.php';
            $content = ob_get_clean();
        }

        return $content;
    }

    // Modify the frontend product field to render multi-select & checkboxes correctly
    public function pre_render_form($form) {
        if (!is_array($form) || empty($form['fields'])) {
            return $form;
        }

        foreach ($form['fields'] as &$field) {
            if ($field instanceof GF_Field && $field->type === 'product') {
                $field->enablePrice = true;
                
                // Set default values for our custom properties
                if (!isset($field->quantityEnabled)) {
                    $field->quantityEnabled = false;
                }
                
                // Handle input type specific modifications
                if (in_array($field->inputType, ['multiselect', 'checkbox'])) {
                    $field->enableMultiple = true;
                }
            }
        }

        return $form;
    }

    public function modify_form_meta($form) {
        if (!is_array($form) || empty($form['fields'])) {
            return $form;
        }

        foreach ($form['fields'] as &$field) {
            if ($field instanceof GF_Field && $field->type === 'product') {
                if (in_array($field->inputType, ['multiselect', 'checkbox'])) {
                    $field->enableMultiple = true;
                    $field->enablePrice = true;
                    
                    // Ensure choices are properly formatted with prices
                    if (!empty($field->choices)) {
                        foreach ($field->choices as &$choice) {
                            if (!isset($choice['price']) || $choice['price'] === '') {
                                $choice['price'] = '0';
                            }
                            // Ensure price is a valid number
                            $choice['price'] = GFCommon::to_number($choice['price']);
                        }
                    }
                }
            }
        }

        return $form;
    }
}

// Initialize the class with proper check for Gravity Forms
add_action('plugins_loaded', function() {
    if (class_exists('GFCommon') && class_exists('GF_Field')) {
        new GF_Enhanced_Product_Fields();
    }
});

// Move JavaScript to a separate file and enqueue properly
remove_action('wp_footer', 'custom_footer_script');
