<script type="text/javascript">
	//hide save settings button in license section
	jQuery(document).ready(function($) {
		$('p.submit').hide();
	});
</script>
<div class="sd-div sd-setting" id="sd-about">
	<label for="sd_api_key" style="float: left; width: 100%;font-weight: bold; cursor: none;"><?php esc_html_e('Hướng dẫn chỉnh về classic checkout', 'ship-depot-translate') ?></label>
	<p class="description"><?php esc_html_e('Hiện tại ShipDepot chưa hỗ trợ cho giao diện Block Checkout nên nếu trang nào mới cài đặt WooCommerce từ phiên bản 8.3 trở lên có thể trang của bạn không hiển thị được ô chọn Tỉnh/Thành, Quận/Huyện, Phường/Xã và thông tin vận chuyển ở trang thanh toán thì vui lòng làm theo video hướng dẫn dưới đây để trở về classic checkout.', 'ship-depot-translate') ?></p>
	<video width="1500" controls muted autoplay>
		<source src="<?php echo SHIP_DEPOT_DIR_URL . 'src/class_checkout_direction.mp4' ?>" type="video/mp4">
		Your browser does not support the video tag.
	</video>
</div>