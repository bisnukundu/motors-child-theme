<?php 
// Complete Rental Add-ons Widget
function register_rental_addons_widget_direct() {
    if (!class_exists('Elementor\Plugin')) {
        return;
    }
    
    // Create the widget class
    class Rental_Addons_Widget_Direct extends \Elementor\Widget_Base {
        
        public function get_name() {
            return 'rental_addons_direct';
        }
        
        public function get_title() {
            return __('BK Rental Add-ons', 'textdomain');
        }
        
        public function get_icon() {
            return 'eicon-product-add-to-cart';
        }
        
        public function get_categories() {
            return ['general'];
        }
        
        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Content', 'textdomain'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );
            
            $this->add_control(
                'title',
                [
                    'label' => __('Title', 'textdomain'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => __('VEHICLE ADD-ONS', 'textdomain'),
                ]
            );
            
            $this->end_controls_section();
        }
        
        protected function render() {
            $settings = $this->get_settings_for_display();
            
            // Query for products with car_option taxonomy
            $args = array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => 'car_option',
                    ),
                ),
            );

            $addons_query = new WP_Query($args);
            
            $output = '<div class="rental-reservation-addons">';
            $output .= '<h4>' . esc_html($settings['title']) . '</h4>';
            
            if ($addons_query->have_posts()) {
                while ($addons_query->have_posts()) {
                    $addons_query->the_post();
                    $product = wc_get_product(get_the_ID());
                    
                    if ($product) {
                        // Get product image
                        $image_url = '';
                        if (has_post_thumbnail()) {
                            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail');
                            $image_url = $image[0];
                        } else {
                            $image_url = wc_placeholder_img_src();
                        }
                        
                        $output .= '<div class="stm_rental_options_archive">';
                        $output .= '<div class="stm_rental_option">';
                        
                        // Image section
                        $output .= '<div class="image">';
                        $output .= '<img decoding="async" width="1" height="1" src="' . esc_url($image_url) . '" class="attachment-thumbnail size-thumbnail wp-post-image" alt="' . esc_attr($product->get_name()) . '">';
                        $output .= '</div>';
                        
                        // Content section
                        $output .= '<div class="stm_rental_option_content">';
                        $output .= '<div class="content">';
                        
                        // Title
                        $output .= '<div class="title">';
                        $output .= '<h4>' . esc_html($product->get_name()) . '</h4>';
                        $output .= '</div>';
                        
                        // More information link
                        $output .= '<div class="stm-more">';
                        $output .= '<a href="#">';
                        $output .= '<span>More information</span>';
                        $output .= '<i class="fas fa-angle-down"></i>';
                        $output .= '</a>';
                        $output .= '</div>';
                        
                        $output .= '</div>';
                        
                        // Meta section (quantity, price, add to cart)
                        $output .= '<div class="meta">';
                        
                        // Quantity
                        $output .= '<div class="quantity">';
                        $output .= '<input type="text" step="1" min="0" max="5" name="quantity" value="1" title="Qty" class="input-text qty text" size="4">';
                        $output .= '<div class="quantity_actions">';
                        $output .= '<span class="plus">+</span>';
                        $output .= '<span class="minus">-</span>';
                        $output .= '</div>';
                        $output .= '</div>';
                        
                        // Price
                        $output .= '<div class="price">';
                        $output .= '<div class="empty_sale_price"></div>';
                        $output .= '<div class="current_price heading-font">' . $product->get_price_html() . '</div>';
                        $output .= '</div>';
                        
                        // Add to cart button
                        $output .= '<div class="stm-add-to-cart heading-font stm-manage-stock-yes">';
                        $output .= '<a href="' . esc_url($product->add_to_cart_url()) . '">';
                        $output .= 'Add';
                        $output .= '</a>';
                        $output .= '</div>';
                        
                        $output .= '</div>';
                        $output .= '<div class="clearfix"></div>';
                        
                        // More info text
                        $description = $product->get_description();
                        if (empty($description)) {
                            $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras sodales tortor est, dictum pharetra lectus facilisis vitae. Proin sodales nec neque sed posuere. Nulla facilisi. Suspendisse tincidunt quisut sagittis. Sed ullamcorper aliquet magna at accumsan. Curabitur fringilla, risus a malesuada mattis, diam quam finibus sapien, sit amet ullamcorper arcu neque a metus. Etiam rutrum orci non ex vehicula, sed egestas metus tristique.';
                        }
                        
                        $output .= '<div class="more">';
                        $output .= $description;
                        $output .= '</div>';
                        
                        $output .= '</div>'; // stm_rental_option_content
                        $output .= '</div>'; // stm_rental_option
                        $output .= '</div>'; // stm_rental_options_archive
                    }
                }
                
                $output .= '<script>
                jQuery(document).ready(function(){
                    var $ = jQuery;
                    $(".stm-manage-stock-yes a").on("click", function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        var stmHref = $(this).attr("href");
                        var quantityValue = $(this).closest(".meta").find(".qty").val();
                        var quantity = "&quantity=" + quantityValue;
                        stmHref += quantity;
                        window.location.href = stmHref;
                    });
                    
                    // Quantity plus/minus functionality
                    $(".quantity_actions .plus").on("click", function() {
                        var input = $(this).closest(".quantity").find("input");
                        var val = parseInt(input.val()) + 1;
                        if(val <= 5) input.val(val);
                    });
                    
                    $(".quantity_actions .minus").on("click", function() {
                        var input = $(this).closest(".quantity").find("input");
                        var val = parseInt(input.val()) - 1;
                        if(val >= 0) input.val(val);
                    });
                    
                    // More info toggle
                    $(".stm-more a").on("click", function(e) {
                        e.preventDefault();
                        var moreDiv = $(this).closest(".stm_rental_option").find(".more");
                        moreDiv.slideToggle();
                    });
                });
                </script>';
                
                wp_reset_postdata();
            } else {
                $output .= '<div class="stm_rental_options_archive">';
                $output .= '<div class="stm_rental_option">';
                $output .= '<h4 class="disabled-heading">No available vehicle add-ons</h4>';
                $output .= '</div>';
                $output .= '</div>';
            }
            
            $output .= '</div>'; // rental-reservation-addons
            
            // Add styles
            $output .= '<style>
            .rental-reservation-addons {
                margin: 20px 0;
            }
            .rental-reservation-addons h4 {
                font-size: 24px;
                margin-bottom: 20px;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: #333;
            }
            .stm_rental_options_archive {
                margin-bottom: 15px;
            }
            .stm_rental_option {
                display: flex;
                border: 1px solid #eee;
                border-radius: 8px;
                padding: 15px;
                background: white;
            }
            .stm_rental_option .image {
                flex: 0 0 80px;
                margin-right: 15px;
            }
            .stm_rental_option .image img {
                width: 100%;
                height: auto;
                max-width: 60px;
            }
            .stm_rental_option_content {
                flex: 1;
            }
            .stm_rental_option_content .title h4 {
                margin: 0 0 10px 0;
                font-size: 18px;
                color: #8B0000;
            }
            .stm-more a {
                color: #007cba;
                text-decoration: none;
                font-size: 14px;
            }
            .stm-more a:hover {
                color: #005a87;
            }
            .meta {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-top: 10px;
            }
            .quantity {
                position: relative;
            }
            .quantity input {
                width: 50px;
                padding: 5px;
                border: 1px solid #ddd;
                text-align: center;
            }
            .quantity_actions {
                position: absolute;
                right: -25px;
                top: 0;
            }
            .quantity_actions span {
                display: block;
                width: 20px;
                height: 15px;
                line-height: 15px;
                text-align: center;
                background: #f5f5f5;
                border: 1px solid #ddd;
                cursor: pointer;
                font-size: 12px;
            }
            .quantity_actions .plus {
                border-bottom: none;
            }
            .stm-add-to-cart a {
                background: #8B0000;
                color: white;
                padding: 8px 15px;
                border-radius: 5px;
                text-decoration: none;
                display: inline-block;
            }
            .stm-add-to-cart a:hover {
                background: #6A0000;
            }
            .more {
                margin-top: 10px;
                font-size: 14px;
                color: #666;
                display: none;
            }
            .clearfix {
                clear: both;
            }
            </style>';
            
            echo $output;
        }
    }
    
    // Register the widget
    \Elementor\Plugin::instance()->widgets_manager->register(new Rental_Addons_Widget_Direct());
}
add_action('elementor/widgets/register', 'register_rental_addons_widget_direct');