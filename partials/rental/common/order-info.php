<?php
// phpcs:disable
$cart_items = stm_get_cart_items();
$car_rent   = $cart_items['car_class'];
$id         = $car_rent['id'];

$priceDate    = PriceForDatePeriod::getDescribeTotalByDays( $car_rent['price'], $id );
$pricePerHour = get_post_meta( $id, 'rental_price_per_hour_info', true );
$discount     = ( class_exists( 'DiscountByDays' ) ) ? DiscountByDays::get_days_post_meta( $id ) : null;
$fixedPrice   = ( class_exists( 'PriceForQuantityDays' ) ) ? PriceForQuantityDays::getFixedPrice( $id ) : null;
$fields       = stm_get_rental_order_fields_values();


// Get insurance from reservation cart data (not from URL)
$insurance_percent = 0;

// Try to get insurance from cart item data
if (!empty($cart_items['items'])) {
    foreach ($cart_items['items'] as $item) {
        if (isset($item['insurance'])) {
            $insurance_percent = intval($item['insurance']);
            break;
        }
    }
}

// If not in cart items, try session data
if ($insurance_percent === 0 && function_exists('WC') && isset(WC()->session)) {
    $session_insurance = WC()->session->get('rental_insurance');
    if ($session_insurance && in_array($session_insurance, [0, 15, 30])) {
        $insurance_percent = $session_insurance;
    }
}

// If still not found, try to get from $_GET (fallback)
if ($insurance_percent === 0 && isset($_GET['insurance']) && in_array($_GET['insurance'], [0, 15, 30])) {
    $insurance_percent = intval($_GET['insurance']);
}

if (!in_array($insurance_percent, [0, 15, 30])) {
    $insurance_percent = 0;
}

// Store in session for next use
if (function_exists('WC') && isset(WC()->session) && $insurance_percent > 0) {
    WC()->session->set('rental_insurance', $insurance_percent);
}

if ( empty( WC()->cart->get_cart() ) ) {
	$order = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) );
	if ( ! empty( $order ) ) {
		$cart_items['total'] = wc_price( $order->get_total() );
	}
} else {
	$cart_items['total'] = stm_get_cart_current_total();
}

$product_id = get_the_ID();

if ( function_exists( 'wpml_object_id_filter' ) ) {
	$product_id = wpml_object_id_filter( $id, 'product', true );
}

?>

	<div class="title">
		<h4><?php echo esc_html( get_the_title( $product_id ) ); ?></h4>
		<div class="subtitle heading-font"><?php echo esc_html( get_post_meta( $product_id, 'cars_info', true ) ); ?></div>
	</div>
	<?php
	if ( has_post_thumbnail( $id ) ) :
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'stm-img-350' );
		if ( ! empty( $image[0] ) ) :
			?>
			<div class="image">
				<img src="<?php echo esc_url( $image[0] ); ?>" />
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<!--Car rent-->
	<div class="stm_rent_table">
		<div class="heading heading-font"><h4><?php esc_html_e( 'Rate', 'motors' ); ?></h4></div>
		<table>
			<thead class="heading-font">
			<tr>
				<td><?php esc_html_e( 'QTY', 'motors' ); ?></td>
				<td><?php esc_html_e( 'Rate', 'motors' ); ?></td>
				<td><?php esc_html_e( 'Subtotal', 'motors' ); ?></td>
			</tr>
			</thead>
			<tbody>
			<?php
			$total = 0;
			if ( empty( $fixedPrice ) && empty( $priceDate['promo_price'] ) ) {
				$total = $car_rent['price'] * $car_rent['days'];
			}
			if( !empty($priceDate) && count($priceDate['promo_price']) > 0) :
				?>
				<?php
				if(count($priceDate['simple_price']) > 0):
					$total = array_sum($priceDate['simple_price']);
					?>
					<tr>
						<td><?php echo sprintf(esc_html__('%s Days', 'motors'), count($priceDate['simple_price'])); ?></td>
						<td>
							<?php
							if (!empty($fixedPrice)){
								echo wc_price($fixedPrice);
							}else {
								echo wc_price($priceDate['simple_price'][0] );
							}
							?>
						</td>
						<td>
							<?php
							if (!empty($fixedPrice)){
								$fixedPrice = $fixedPrice * count($priceDate['simple_price']);
								$total = $fixedPrice;
								echo wc_price($fixedPrice);
							} else{
								echo wc_price(array_sum($priceDate['simple_price']));
							}
							?>
						</td>
					</tr>
				<?php
				endif;

				if ( count( $priceDate['promo_price'] ) > 0 ) :
					$total          = $total + array_sum( $priceDate['promo_price'] );

					$priceDateTotal = $total;
					$promo_prices   = array_count_values( $priceDate['promo_price'] );

					foreach ( $promo_prices as $k => $val ) :

						$period_total_price = $val * $k;
						?>
						<tr>
							<td><?php echo sprintf(esc_html__('%s Days', 'motors'), $val); ?></td>
							<td>
								<?php echo wc_price($k); ?>
							</td>
							<td><?php echo wc_price($period_total_price); ?></td>
						</tr>
					<?php
					endforeach;
				endif; ?>

			<?php else: ?>
				<!--FIXED PRICE-->
				<?php
				if(count($priceDate['promo_price']) == 0 && !empty($fixedPrice)) :
					$days = $car_rent['days'];
					$price = $car_rent['price'];
					if ( is_array( $fixedPrice ) ) {
						foreach ( $fixedPrice as $k => $val ) {
							$fixedDays = $k;
							if( $days >= $k ) {
								$price = $val;
							}
						}
					} else {
						$price = $fixedPrice;
					}
					$total = $price * $days;
					?>
					<tr>
						<td><?php echo sprintf(esc_html__('%s Days', 'motors'), $car_rent['days']); ?></td>
						<td>
							<?php echo wc_price($price); ?>
							<?php stm_getInfoWindowPriceManip($id); ?>
						</td>
						<td><?php echo wc_price($total); ?></td>
					</tr>
				<?php else :
					//to show price if date and time not selected
					if ( empty( $car_rent['days'] ) &&  empty( isset( $car_rent['hours'] ) ) ) {
						$car_rent['days']     = 1;
						$car_rent['subtotal'] = $car_rent['price'];
						$cart_items['total']  = wc_price( $car_rent['price'] );
					}
					?>
					<tr>
						<td><?php echo sprintf( esc_html__( '%s Days', 'motors' ), $car_rent['days'] ); ?></td>
						<td>
							<?php echo wc_price( $car_rent['price'] ); ?>
						</td>
						<td><?php
							if ( ! empty( $car_rent['subtotal'] ) ) {
								echo wc_price( $car_rent['subtotal'] );
							} else {
								echo wc_price( $total );
							}
							?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endif; ?>
			<?php
			if(!empty($pricePerHour) && !empty($car_rent['hours'])):
				$total = ($total != ($car_rent['hours'] * $pricePerHour)) ? $total + ($car_rent['hours'] * $pricePerHour) : $car_rent['hours'] * $pricePerHour;
				?>
				<tr>
					<td><?php echo sprintf(esc_html__('%s Hours', 'motors'), $car_rent['hours']); ?></td>
					<td>
						<?php echo wc_price( $pricePerHour ); ?>
					</td>
					<td><?php echo wc_price($car_rent['hours'] * $pricePerHour); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( apply_filters( 'stm_me_get_nuxy_mod', true, 'enable_office_location_fee' ) ) : ?>
			<?php if ( 'on' === $fields['return_same'] && apply_filters( 'stm_me_get_nuxy_mod', true, 'enable_fee_for_same_location' ) && ! empty( $fields['pickup_location_fee'] ) ) :
					$total = $total + $fields['pickup_location_fee'];
					?>
				<tr>
					<td colspan="2"><?php esc_html_e( 'Return Location Fee', 'motors' ); ?></td>
					<td class="two-cols"><?php echo wc_price( $fields['pickup_location_fee'] ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( 'off' === $fields['return_same'] && ! empty( $fields['return_location_fee'] ) ) :
					$total = $total + $fields['return_location_fee'];
					?>
				<tr>
					<td colspan="2"><?php esc_html_e( 'Return Location Fee', 'motors' ); ?></td>
					<td class="two-cols"><?php echo wc_price( $fields['return_location_fee'] ); ?></td>
				</tr>
			<?php endif; ?>
			<?php endif; ?>
			<?php
			if ( !empty( $discount ) ) :
				$currentDiscount = 0;
				$days = 0;
				foreach ( $discount as $k => $val ) {
					if($val['days'] <= $car_rent['days']) {
						$days = $val['days'];
						$currentDiscount = $val['percent'];
					}
				}

				$forDiscount = $total;
				$total = $total - (($total / 100) * $currentDiscount);
				?>
				<tr>
					<td colspan="2" class="stm-discount"><?php echo sprintf(__('Discount: <span class="show-discount-popup">%s Days and more %s sale</span>', 'motors'), $days, $currentDiscount . '%');?></td>
					<td class="sb-discount-info two-cols"><?php echo '- ' . wc_price( ($forDiscount / 100) * $currentDiscount ); ?></td>
				</tr>
			<?php endif; ?>
			
			<?php if ( $insurance_percent > 0 ) : ?>
				<!-- INSURANCE ROW -->
				<tr>
					<td colspan="2"><?php esc_html_e( 'Insurance Coverage', 'motors' ); ?> (<?php echo $insurance_percent; ?>%)</td>
					<td class="two-cols">
						<?php
						// Calculate insurance amount: insurance_percent % of the current total
						$insurance_amount = $insurance_percent * $car_rent['days'];
						echo wc_price($insurance_amount);					
						?>
					</td>
				</tr>
			<?php endif; ?>
			
			</tbody>
			<tfoot class="heading-font">
			<tr>
				<td colspan="2"><?php esc_html_e( 'Rental Charges Rate', 'motors' ); ?></td>
				<td><?php 
					if ($insurance_percent > 0) {
						// Add insurance to total
						$insurance_amount = $insurance_percent * $car_rent['days'];
						$new_total = $total + $insurance_amount;
						echo wp_kses_post(wc_price($new_total));
					} else {
						echo wp_kses_post(wc_price($total));
					}
				?></td>
			</tr>
			</tfoot>
		</table>
	</div>

	<!--Add-ons-->
	<?php if ( ! empty( $cart_items['options'] ) ) : ?>
		<div class="stm_rent_table">
			<div class="heading heading-font"><h4><?php esc_html_e( 'Add-ons', 'motors' ); ?></h4></div>
			<table>
				<thead class="heading-font">
					<tr>
						<td><?php esc_html_e( 'QTY', 'motors' ); ?></td>
						<td><?php esc_html_e( 'Rate', 'motors' ); ?></td>
						<td><?php esc_html_e( 'Subtotal', 'motors' ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="3" class="divider"></td>
					</tr>
					<?php foreach ( $cart_items['options'] as $car_option ) : ?>
						<tr>
							<td>
								<?php
								$opt_days          = ( ! empty( $car_option['opt_days'] ) ) ? $car_option['opt_days'] : 1;
								$single_pay_option = (bool) get_post_meta( $car_option['id'], '_car_option', true );
								if ( $single_pay_option ) {
									echo sprintf( esc_html__( '%1$s x %2$1s', 'motors' ), $car_option['quantity'], $car_option['name'] );
								} else {
									/* translators: 1. quantity, 2. option name, 3. number of days */
									echo sprintf( esc_html__( '%1$s x %2$1s %3$s %4$s day(s)', 'motors' ), $car_option['quantity'], $car_option['name'], esc_html__( 'for', 'motors' ), $car_option['opt_days'] );
								}
								?>
							</td>
							<td><?php echo wc_price( $car_option['price'] ); ?></td>
							<td><?php echo wc_price( $car_option['total'] ); ?></td>
						</tr>
						<tr>
							<td colspan="3" class="divider"></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot class="heading-font">
					<tr>
						<td colspan="2"><?php esc_html_e( 'Add-ons Charges Rate', 'motors' ); ?></td>
						<td><?php echo wc_price( $cart_items['option_total'] ); ?></td>
					</tr>
				</tfoot>
			</table>
		</div>
	<?php endif; ?>

	<?php get_template_part( 'partials/rental/common/tax' ); ?>

	<?php get_template_part( 'partials/rental/common/coupon' ); ?>

	<div class="stm-rent-total heading-font">
		<table>
			<tr>
				<td><?php esc_html_e( 'Estimated total', 'motors' ); ?></td>
				<td>
					<?php 
					// Calculate final total including insurance
					$final_total = $total;
					if ($insurance_percent > 0) {
						$insurance_amount = $insurance_percent * $car_rent['days'];
						$final_total += $insurance_amount;
					}
					
					// Add tax if applicable
					if (!empty($cart_items['taxes'])) {
						$final_total += $cart_items['taxes']['amount'];
					}
					
					echo wp_kses_post(wc_price($final_total));
					?>
				</td>
			</tr>
		</table>
	</div>
<?php
if ( ! empty( $discount ) ) :
	$desc = apply_filters( 'stm_me_get_nuxy_mod', '', 'discount_program_desc' );
	?>
<div id="stm-discount-by-days-popup" class="stm-promo-popup-wrap">
	<div class="stm-promo-popup">
		<h5><?php echo __( 'Discount program', 'motors' ); ?></h5>
		<?php if ( ! empty( $desc ) ) : ?>
			<div class="stm-disc-prog-desc">
				<?php echo esc_html( $desc ); ?>
			</div>
		<?php endif; ?>
		<div class="stm-table stm-pp-head">
			<div class="stm-pp-row stm-pp-qty heading-font"><?php _e( 'QTY', 'motors' ); ?></div>
			<div class="stm-pp-row stm-pp-subtotal heading-font"><?php _e( 'DISCOUNT', 'motors' ); ?></div>
		</div>
		<?php foreach ( $discount as $k => $val ) : ?>
		<div class="stm-table stm-pp-discount">
			<div class="stm-pp-row"><?php echo sprintf( __( '%s Days and more', 'motors' ), $val['days'] ); ?></div>
			<div class="stm-pp-row"><?php echo esc_html( '- ' . $val['percent'] . '%' ); ?></div>
		</div>
		<?php endforeach; ?>
		<div class="stm-rental-ico-close" data-close-id="stm-discount-by-days-popup"></div>
	</div>
</div>
<?php endif; ?>