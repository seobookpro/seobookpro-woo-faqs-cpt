<?php
/*
Plugin Name: SEO Book Pro Custom WooCommerce Single Product Tabs FAQ's
Description: Adds a custom FAQ's tab to WooCommerce Single Product page.
Version: 0.0.4 r-c
Author: SEO Book Pro
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Add a custom product tab
add_filter('woocommerce_product_tabs', 'sbp_woo_custom_product_tab');

function sbp_woo_custom_product_tab($tabs) {
    global $post;
    $custom_faq_content = get_post_meta($post->ID, 'sbp_wc_cpt_custom_faq_content', true);
    $faqs = json_decode(wp_unslash($custom_faq_content), true);

    // Only add the tab if there are FAQs
    if (!empty($faqs) || get_option('sbp_wc_cpt_display_empty_tab') === 'yes') {
        $tabs['custom_faq_tab'] = array(
            'title'    => __('Product FAQ`s', 'sbp-woo-faqs-cpt'),
            'priority' => 60,
            'callback' => 'sbp_woo_custom_product_faq_tab_content'
        );
    }
    return $tabs;
}

// The content of the custom tab
function sbp_woo_custom_product_faq_tab_content() {
    global $post;
    $custom_faq_content = get_post_meta($post->ID, 'sbp_wc_cpt_custom_faq_content', true);
    $faqs = json_decode(wp_unslash($custom_faq_content), true);

    $faq_count = is_array($faqs) ? count($faqs) : 0;
    echo '<style>.faqs-content p { padding: 0px !important; margin: 0px 0px 0px 0px; } details[open] summary { font-size: 14px; margin: 0px 0px 10px 0px; font-weight: 600; color: #000; border-bottom: 1px dotted #000; padding: 0px 0px 10px 0px; } .general-faqs{ padding: 10px 20px 10px 20px; margin:0px auto; width: 50%; background: #000; color: #fff; font-size: 16px; font-weight: 600; text-align: center; display: block; }</style>';
    echo '<h2 style="font-size: 32px;font-weight: 600;color: #000;border-bottom: 2px solid #000;padding: 0px 0px 10px 0px;margin: 0px 0px 20px 0px;box-shadow: 0px 15px 10px -14px #000;">Product FAQs (' . $faq_count . ')</h2>';
    echo '<div itemscope itemtype="https://schema.org/FAQPage">';
    if ($faq_count > 0) {
        foreach ($faqs as $faq) {
            echo '<div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question" style="margin: 0px 0px 20px 0px; padding: 10px 10px 10px 10px; border: 1px dotted #000; background: rgba(0, 0, 0, 0); box-shadow: 0px 15px 10px -10px #000;">';
            echo '<details><summary itemprop="name">' . esc_html($faq['question']) . '</summary>';
            echo '<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">';
            echo '<div itemprop="text" style="font-size: 14px; padding: 5px 15px 5px 15px; background: rgba(204, 204, 204, 0.15); color: #000; font-weight: 500; border-left: 3px solid #000;" class="faqs-content">' . wpautop($faq['answer']) . '</div>';
            echo '</div>';
            echo '</details>';
            echo '</div>';
        }
    } else {
        $general_faq_link = get_option('sbp_wc_cpt_general_faq_link');
        echo '<p>No FAQs available.</p>';
        echo '<p>Read our General FAQs Section</p>';
        echo '<a href="' . esc_url($general_faq_link) . '" target="_blank" rel="bookmark" type="link" role="button" aria-label="Read our General FAQs Section" class="general-faqs">Read our General FAQs Section</a>';
    }
    echo '</div>';
}

// Add custom fields to the product edit screen
add_action('woocommerce_product_options_general_product_data', 'sbp_woo_custom_product_fields');
function sbp_woo_custom_product_fields() {
    global $post;
    $custom_faq_content = get_post_meta($post->ID, 'sbp_wc_cpt_custom_faq_content', true);
    $faqs = json_decode(wp_unslash($custom_faq_content), true);

    echo '<div id="faq-fields-container">';
    echo '<label>' . __('Product FAQs', 'sbp-woo-faqs-cpt') . '</label>';
    if (!empty($faqs)) {
        foreach ($faqs as $index => $faq) {
            echo '<div class="faq-field-group">';
            echo '<input type="text" name="faq_question[]" value="' . esc_attr($faq['question']) . '" placeholder="FAQ Question" />';
            echo '<textarea name="faq_answer[]" placeholder="FAQ Answer">' . esc_textarea($faq['answer']) . '</textarea>';
            echo '<button type="button" class="button remove-faq">Remove FAQ</button>';
            echo '</div>';
        }
    }
    echo '</div>';
    echo '<button type="button" class="button add-faq">Add FAQ</button>';
}

// Save custom fields
add_action('woocommerce_process_product_meta', 'sbp_woo_save_custom_product_fields');
function sbp_woo_save_custom_product_fields($post_id) {
    $faq_questions = isset($_POST['faq_question']) ? array_map('sanitize_text_field', wp_unslash($_POST['faq_question'])) : array();
    $faq_answers = isset($_POST['faq_answer']) ? array_map('wp_kses_post', wp_unslash($_POST['faq_answer'])) : array();

    $faqs = array();
    for ($i = 0; $i < count($faq_questions); $i++) {
        if (!empty($faq_questions[$i]) && !empty($faq_answers[$i])) {
            $faqs[] = array(
                'question' => $faq_questions[$i],
                'answer'   => $faq_answers[$i]
            );
        }
    }

    update_post_meta($post_id, 'sbp_wc_cpt_custom_faq_content', wp_slash(json_encode($faqs)));
}

// Add scripts to handle dynamic fields
add_action('admin_footer', 'sbp_woo_custom_product_fields_scripts');
function sbp_woo_custom_product_fields_scripts() {
    if ('product' !== get_post_type()) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#faq-fields-container').on('click', '.remove-faq', function() {
                $(this).closest('.faq-field-group').remove();
            });

            $('.add-faq').on('click', function() {
                $('#faq-fields-container').append('<div class="faq-field-group">' +
                    '<input type="text" name="faq_question[]" placeholder="FAQ Question" />' +
                    '<textarea name="faq_answer[]" placeholder="FAQ Answer"></textarea>' +
                    '<button type="button" class="button remove-faq">Remove FAQ</button>' +
                '</div>');
            });
        });
    </script>
    <?php
}

// Add settings page under WooCommerce menu
add_action('admin_menu', 'sbp_woo_add_settings_page');
function sbp_woo_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __('FAQ Settings', 'sbp-woo-faqs-cpt'),
        __('FAQ Settings', 'sbp-woo-faqs-cpt'),
        'manage_options',
        'sbp-woo-faqs-cpt-settings',
        'sbp_woo_render_settings_page'
    );
}

// Render settings page
function sbp_woo_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('FAQ Settings', 'sbp-woo-faqs-cpt'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('sbp_woo_cpt_settings_group');
            do_settings_sections('sbp-woo-faqs-cpt-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'sbp_woo_register_settings');
function sbp_woo_register_settings() {
    register_setting('sbp_woo_cpt_settings_group', 'sbp_wc_cpt_display_empty_tab');
    register_setting('sbp_woo_cpt_settings_group', 'sbp_wc_cpt_general_faq_link');

    add_settings_section('sbp_woo_cpt_settings_section', __('FAQ Settings', 'sbp-woo-faqs-cpt'), null, 'sbp-woo-faqs-cpt-settings');





    add_settings_field(
        'sbp_wc_cpt_display_description_tab',
        __('<h2>FAQ Settings General Configuration</h2>', 'sbp-woo-faqs-cpt'),
        'sbp_woo_display_description_empty_tab_callback',
        'sbp-woo-faqs-cpt-settings',
        'sbp_woo_cpt_settings_section'
    );


    add_settings_field(
        'sbp_wc_cpt_display_empty_tab',
        __('<h2>Display FAQ Tab if Empty</h2>', 'sbp-woo-faqs-cpt'),
        'sbp_woo_display_empty_tab_callback',
        'sbp-woo-faqs-cpt-settings',
        'sbp_woo_cpt_settings_section'
    );

    add_settings_field(
        'sbp_wc_cpt_general_faq_link',
        __('<h2>General FAQ Link</h2>', 'sbp-woo-faqs-cpt'),
        'sbp_woo_general_faq_link_callback',
        'sbp-woo-faqs-cpt-settings',
        'sbp_woo_cpt_settings_section'
    );
}



function sbp_woo_display_description_empty_tab_callback() {
  $option = get_option('sbp_wc_cpt_display_description_tab', '');
  echo '<style>
  .general-setting {
    width: auto;
    background: #ccc;
    border: 1px solid #000;
    box-shadow: 0px 10px 15px -10px #000;
    min-width: auto;
    max-width: 90%;
    margin: 20px auto;
    padding: 20px;

  }
  details.general-setting-style {
    font-size: 24px;
    margin: 10px 0px 10px 0px;
    padding: 5px 0px 5px 0px;
    border-bottom: 1px solid #000;
    width: auto;
    min-width: auto;
    max-width: 90%;
  }
  </style>';
  $plugin_description = "
  <div class='general-setting'>

      <details class='general-setting-style'>
          <summary>Introduction</summary>
          <p>The FAQ Settings Configuration page allows you to tailor the behavior of the FAQ tab on your WooCommerce single product pages. This configuration ensures that your product pages provide relevant information to customers, enhancing their shopping experience and potentially increasing conversions.</p>
      </details>

    <details class='general-setting-style'>
          <summary>Options Available</summary>
          <p>On this page, you will find several options that control how and when the FAQ tab is displayed. These settings are designed to give you flexibility in managing product-specific FAQs.</p>
      </details>

    <details class='general-setting-style'>
          <summary>General FAQ Link</summary>
          <p>The 'General FAQ Link' option allows you to specify a URL that points to your general FAQ page. This is particularly useful when individual products do not have their own FAQs. By providing a link to a centralized FAQ page, you can ensure that your customers always have access to important information. Simply enter the URL of your general FAQ page in the text field provided</p>

      </details>

    <details class='general-setting-style'>
          <summary>Display FAQ Tab if Empty</summary>
          <p>Display FAQ Tab if Empty' option lets you decide whether the FAQ tab should be visible on product pages that do not have any FAQs added. This setting can be set to 'Yes' or 'No'</p>
          <ul>
              <li>Yes:The FAQ tab will be displayed even if there are no FAQs specific to the product. This can be useful if you want to ensure that the FAQ section is always present, potentially guiding users to your general FAQ page.</li>
              <li><strong>No:</strong> The FAQ tab will only be displayed if there are FAQs added to the product. This option helps keep the product page clean and relevant, showing the FAQ tab only when there is specific information available.</li>
          </ul>

      </details>

    <details class='general-setting-style'>
          <summary>How to Configure</summary>
          <p>To configure these settings:'</p>
          <ol>
              <li>Navigate to the FAQ Settings page under the WooCommerce settings menu.</li>
              <li>Adjust the 'General FAQ Link' by entering the URL of your central FAQ page.</li>
              <li>Select 'Yes' or 'No' from the dropdown menu for the 'Display FAQ Tab if Empty' option, based on your preference.</li>
              <li>Save your changes by clicking the 'Save Changes' button.</li>
          </ol>
          <p>These settings ensure that your FAQ tab is used effectively, providing valuable information to your customers and enhancing their overall experience on your WooCommerce store.</p>
      </details>

  </div>

  ";

       echo $plugin_description;


}

function sbp_woo_display_empty_tab_callback() {

    $option = get_option('sbp_wc_cpt_display_empty_tab', 'no');
    ?>
    <select name="sbp_wc_cpt_display_empty_tab">
        <option value="yes" <?php selected($option, 'yes'); ?>><?php _e('Yes', 'sbp-woo-faqs-cpt'); ?></option>
        <option value="no" <?php selected($option, 'no'); ?>><?php _e('No', 'sbp-woo-faqs-cpt'); ?></option>
    </select>
    <p class="description"><?php _e('Choose whether to display the FAQ tab even if no FAQs are added.', 'sbp-woo-faqs-cpt'); ?></p>
    <?php
}

function sbp_woo_general_faq_link_callback() {
    $option = get_option('sbp_wc_cpt_general_faq_link', '');
    ?>
    <input type="text" name="sbp_wc_cpt_general_faq_link" value="<?php echo esc_attr($option); ?>" class="regular-text">
    <p class="description"><?php _e('Enter the URL for your general FAQ page.', 'sbp-woo-faqs-cpt'); ?></p>
    <?php
}
?>
