<script type="text/javascript">
	//hide save settings button in license section
	jQuery(document).ready(function($) {
		$('p.submit').hide();
	});
</script>
<div class="sd-div sd-setting" id="sd-about">
	<label for="sd_api_key" style="float: left; width: 100%;font-weight: bold; cursor: none;"><?php esc_html_e('Giới thiệu', 'ship-depot-translate') ?></label>
	<p class="description"><?php esc_html_e('ShipDepot hiểu sự phức tạp của xử lý vận đơn trong tình hình nhiều đơn vị vận chuyển và số lượng dịch vụ vận chuyển như hiện nay. Giải pháp của ShipDepot là tạo ra một nền tảng đơn giản hoá việc xử lý vận đơn với các ưu điểm:', 'ship-depot-translate') ?></p>
	<ul>
		<li class="sd-advantage">
			<?php esc_html_e('Chỉ một tài khoản có thể theo dõi đơn hàng với nhiều đơn vị vận chuyển', 'ship-depot-translate') ?>
		</li>
		<li class="sd-advantage">
			<?php esc_html_e('Chủ động lựa chọn đơn vị vận chuyển mà không cần đăng ký tài khoản với từng đơn vị vận chuyển', 'ship-depot-translate') ?>
		</li>
		<li class="sd-advantage">
			<?php esc_html_e('Chủ động điều chỉnh biểu phí giao hàng đối với khách hàng của bạn', 'ship-depot-translate') ?>
		</li>
		<li class="sd-advantage">
			<?php esc_html_e('Hưởng chiết khấu phí giao hàng', 'ship-depot-translate') ?>
		</li>
		<li class="sd-advantage">
			<?php esc_html_e('Quản lý dư nợ thu hộ COD ở một chỗ', 'ship-depot-translate') ?>
		</li>
	</ul>

	<label for="sd_api_key" style="float: left; width: 100%;font-weight: bold; cursor: none; margin-top: 10px;"><?php esc_html_e('Trợ giúp', 'ship-depot-translate') ?></label>
	<p class="description"><?php esc_html_e('Video và các hướng dẫn sử dụng có thể xem tại ', 'ship-depot-translate') ?>
		<a href="<?php echo esc_url(SHIP_DEPOT_SITE) ?>" target="_blank"><?php echo esc_url(SHIP_DEPOT_SITE) ?></a>
	</p>
	<label for="sd_api_key" style="float: left; width: 100%;font-weight: bold; cursor: none;"><?php esc_html_e('License', 'ship-depot-translate') ?></label>
	<p class="description"><?php esc_html_e('Miễn phí đối với plugin và một số tính năng.', 'ship-depot-translate') ?>
	</p>
	<label for="sd_api_key" style="float: left; width: 100%;font-weight: bold; cursor: none;"><?php esc_html_e('Phiên bản plugin', 'ship-depot-translate') ?></label>
	<p class="description"><?php echo 'Version ' . esc_html(SHIP_DEPOT_VERSION) ?></p>
</div>