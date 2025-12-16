<?php
if (!defined('ABSPATH')) {
    die("Go Back.");
}


define("BK_FILE_PATH", get_stylesheet_directory());

require_once(BK_FILE_PATH . "/Backend/Controller/Car_Filter_By_Tag.php");

use Bisnu\Backend\Controller\Car_Filter_By_Tag;


new Car_Filter_By_Tag();


function add_insurance_to_rental_cart_js() {
    if (is_page('rental-cart') || is_page('booking')) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get current insurance from hidden input or sessionStorage
            const insuranceInput = document.querySelector('input[name="insurance"]');
            let insuranceValue = '0';
            
            if (insuranceInput && insuranceInput.value) {
                insuranceValue = insuranceInput.value;
            } else if (sessionStorage.getItem('selected_insurance')) {
                insuranceValue = sessionStorage.getItem('selected_insurance');
            } else if (localStorage.getItem('selected_insurance')) {
                insuranceValue = localStorage.getItem('selected_insurance');
            }
            
            // Add insurance parameter to all rental cart related links
            const links = document.querySelectorAll('a[href*="rental-cart"], a[href*="booking"], a[href*="add-to-cart"]');
            links.forEach(link => {
                const url = new URL(link.href);
                if (!url.searchParams.get('insurance')) {
                    url.searchParams.set('insurance', insuranceValue);
                    link.href = url.toString();
                }
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'add_insurance_to_rental_cart_js');

// Add insurance to cart item data when booking
function add_insurance_to_rental_cart($cart_item_data, $product_id, $variation_id = 0, $quantity = 0) {
    if (isset($_GET['insurance']) && in_array($_GET['insurance'], [0, 15, 30])) {
        $insurance = intval($_GET['insurance']);
        $cart_item_data['insurance'] = $insurance;
        
        // Also store in session
        if (function_exists('WC') && isset(WC()->session)) {
            WC()->session->set('rental_insurance', $insurance);
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_insurance_to_rental_cart', 10, 4);

// Automatically add insurance parameter to reservation links
function add_insurance_to_reservation_links($link, $product = null) {
    if (isset($_GET['insurance']) && in_array($_GET['insurance'], [0, 15, 30])) {
        $insurance = intval($_GET['insurance']);
        
        // Only add to reservation/booking related links
        if (strpos($link, 'reservation') !== false || strpos($link, 'add-to-cart') !== false) {
            $separator = strpos($link, '?') !== false ? '&' : '?';
            $link = $link . $separator . 'insurance=' . $insurance;
        }
    }
    return $link;
}
add_filter('the_permalink', 'add_insurance_to_reservation_links', 20, 2);
add_filter('post_permalink', 'add_insurance_to_reservation_links', 20, 2);

// Hook into Motors theme specific functions
function modify_motors_reservation_url($url) {
    if (isset($_GET['insurance']) && in_array($_GET['insurance'], [0, 15, 30])) {
        $insurance = intval($_GET['insurance']);
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url = $url . $separator . 'insurance=' . $insurance;
    }
    return $url;
}
add_filter('stm_reservation_url', 'modify_motors_reservation_url');
add_filter('stm_rental_cart_url', 'modify_motors_reservation_url');





// Add insurance fee to cart total
function add_insurance_fee_to_checkout($cart) {
    if (is_checkout() && !is_admin()) {
        // Get insurance from URL or session
        $insurance_percent = 0;
        
        if (isset($_GET['insurance']) && in_array($_GET['insurance'], [0, 15, 30])) {
            $insurance_percent = intval($_GET['insurance']);
        } elseif (function_exists('WC') && isset(WC()->session)) {
            $session_insurance = WC()->session->get('rental_insurance');
            if ($session_insurance && in_array($session_insurance, [0, 15, 30])) {
                $insurance_percent = $session_insurance;
            }
        }
        
        if ($insurance_percent > 0) {
            // Calculate insurance amount based on cart subtotal
            $subtotal = $cart->get_subtotal();
            $insurance_amount = ($subtotal * $insurance_percent) / 100;
            
            // Add insurance fee
            $cart->add_fee('Insurance Coverage (' . $insurance_percent . '%)', $insurance_amount);
        }
    }
}
add_action('woocommerce_cart_calculate_fees', 'add_insurance_fee_to_checkout');