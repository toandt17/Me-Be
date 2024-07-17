<?php

defined('ABSPATH') || exit;

final class Ship_Depot
{
    public $version = '1.0.0';
    protected static $_instance = null;
    public $settings;
    public $units;
    public $store_info;
    public $address_book;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->autoload();
        $this->loadTextdomain();
        $this->hooks();
        $this->create_settings();
        $this->init_data();
    }

    private function create_settings()
    {
        $settings = new Ship_Depot_Settings_Init();
    }

    private function init_data()
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        $data = new Ship_Depot_Data();
    }


    private function autoload()
    {
        //
        require_once SHIP_DEPOT_DIR_PATH . 'helper/class-ship-depot-log.php';
        require_once SHIP_DEPOT_DIR_PATH . 'helper/class-function-helper.php';
        require_once SHIP_DEPOT_DIR_PATH . 'helper/class-function-protect-data.php';
        require_once SHIP_DEPOT_DIR_PATH . 'helper/class-function-get-data.php';
        require_once SHIP_DEPOT_DIR_PATH . 'helper/class-default-data.php';
        require_once SHIP_DEPOT_DIR_PATH . 'helper/class-shipping-helper.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-data.php';
        require_once SHIP_DEPOT_DIR_PATH . 'rest-api/class-shipdepot-webhook.php';
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-settings-init.php';
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/ShippingStatus/class-extra-shipping-status.php';
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/class-ship-depot-shipping-zone.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/ShippingMethod/class-SHIPDEPOT-shipping-method.php';
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Address/class-address-helper.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Address/address-ajax.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Address/class-custom-order-fields.php';
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-base-model.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-province.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-district.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-cod.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-coupon.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-cod-failed.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-cod-failed-notice.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-insurance.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-package.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-item.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-shop-info.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-courier.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-ship-from-station.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-ship-station.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-courier-service.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-customer.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-receiver.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/class-shipping-status.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/resultDTO.php';
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/ShippingFee/class-courier-response.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/ShippingFee/class-shipping-fee-response.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/ShippingFee/class-shipping-fee-info.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/ShippingFee/class-no-markup-shipping-fee.php';
        //
        //
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-settings.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-markup.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-markup-time-condition.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-markup-order-condition.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-markup-option.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-markup-courier-service.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Model/FeeSetting/class-fee-markup-courier-condition.php';
        //
        //
        if (is_admin()) {
            require_once SHIP_DEPOT_DIR_PATH . 'includes/Address/admin/class-custom-profile-fields.php';
            require_once SHIP_DEPOT_DIR_PATH . 'includes/Address/admin/class-custom-admin-order-fields.php';
            require_once SHIP_DEPOT_DIR_PATH . 'page/admin/orders/sd-order-detail.php';
            require_once SHIP_DEPOT_DIR_PATH . 'page/admin/orders/sd-order-list.php';
            require_once SHIP_DEPOT_DIR_PATH . 'includes/class-admin-notices.php';
        } else {
            require_once SHIP_DEPOT_DIR_PATH . 'includes/Address/frontend/class-custom-checkout-fields.php';
            require_once SHIP_DEPOT_DIR_PATH . 'page/frontend/sd-cart-page.php';
            //Move sd-checkout-page out because require inside this block admin ajax cannot call to destination function code in this page
        }
        //Move sd-checkout-page out because require inside this block admin ajax cannot call to destination function code in this page
        require_once SHIP_DEPOT_DIR_PATH . 'page/frontend/sd-checkout-page.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Order/class-order-shipping.php';
    }

    private function loadTextdomain()
    {
        // Set filter for plugin's languages directory
        //load_plugin_textdomain('ship-depot-translate', false, dirname(plugin_basename(SHIP_DEPOT_PLUGIN_FILE)) . '/languages');
    }

    private function hooks()
    {
        add_action('admin_notices', array($this, 'check_notify_update_plugin'), 99);
        add_action('admin_notices', array($this, 'notify_switch_classic_checkout'), 99);
        if (!Ship_Depot_Helper::is_woocommerce_activated()) {
            add_action('admin_notices', array($this, 'no_woocommerce_deactivated'), 99);
            add_action('admin_init', array($this, 'auto_deactivate'));
            return;
        }

        add_action('wp_enqueue_scripts', array($this, 'front_enqueue_script'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script'));
        //Add for module js
        //add_filter('script_loader_tag', array($this, 'add_script_type_attribute'), 10, 3);
        //
        if (is_admin()) {
            update_option('woocommerce_default_country', 'VN');
            //add_action('woocommerce_init', array($this, 'hello_myfriend'));
        }
        //add_filter('woocommerce_locate_template', array($this, 'log_template'), 10, 3);
        //add_filter('woocommerce_locate_template', array($this, 'woocommerce_locate_template'), 10, 3);
        //Extend time out request
        add_filter('http_request_timeout', array($this, 'sd_http_request_timeout_extend'));
    }

    public function log_template($template, $template_name, $template_path)
    {
        Ship_Depot_Logger::wrlog('[log_template] template: ' . print_r($template, true));
        Ship_Depot_Logger::wrlog('[log_template] template_name: ' . print_r($template_name, true));
        Ship_Depot_Logger::wrlog('[log_template] template_path: ' . print_r($template_path, true));
        return $template;
    }
    //Add for module js
    function add_script_type_attribute($tag, $handle, $src)
    {
        Ship_Depot_Logger::wrlog('[add_script_type_attribute] handle: ' . $handle);
        Ship_Depot_Logger::wrlog('[add_script_type_attribute] src: ' . $src);
        if (str_starts_with($handle, 'sd-module')) {
            $tag = '<script type="module" src="' . esc_url($src) . '" id="' . esc_attr($handle) . '-js"></script>';
        }
        Ship_Depot_Logger::wrlog('[add_script_type_attribute] tag: ' . $tag);
        return $tag;
    }

    public function front_enqueue_script()
    {
        // wp_enqueue_style('sd-bootstrap-style', SHIP_DEPOT_DIR_URL . 'assets/css/bootstrap/bootstrap.min.css', array(), '5.2.3', 'all');
        wp_enqueue_style('sd-main-style', SHIP_DEPOT_DIR_URL . 'assets/css/main.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-front', SHIP_DEPOT_DIR_URL . 'assets/css/fe-checkout.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-front-custom', SHIP_DEPOT_DIR_URL . 'assets/css/fe-checkout-custom.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-dialog-style', SHIP_DEPOT_DIR_URL . 'assets/css/dialog.css', array(), '1.0.0', 'all');
        //is_product() || is_cart() || is_checkout() || is_account_page()
        if (is_checkout()) {
            wp_enqueue_script('sd-fe-checkout', SHIP_DEPOT_DIR_URL . 'assets/js/fe-checkout.js', array('jquery'), $this->version, true);
            wp_localize_script('sd-fe-checkout', 'sd_fe_checkout_params', array(
                'ajax' => array(
                    'url' => admin_url('admin-ajax.php'),
                    'ship_depot_host_api' => esc_url(SHIP_DEPOT_HOST_API),
                ),
                'l10n' => array(
                    'loading' => esc_html__('Đang tải ...', 'ship-depot-translate'),
                    'calculating_shipping' => esc_html__('Đang tính phí ship ...', 'ship-depot-translate'),
                    'creating_order' => esc_html__('Đang tạo đơn hàng ...', 'ship-depot-translate'),
                    'processing' => esc_html__('Processing', 'ship-depot-translate'),
                    'create_order' => esc_html__('Tạo mới đơn hàng', 'ship-depot-translate'),
                    'create_order_again' => esc_html__('Tạo lại đơn hàng', 'ship-depot-translate'),
                    'create_order_successful' => esc_html__('Tạo mới đơn hàng thành công', 'ship-depot-translate'),
                    'select_ward' => esc_html(SD_SELECT_WARD_TEXT),
                    'select_district' => esc_html(SD_SELECT_DISTRICT_TEXT),
                    'ghtk_province_spc' => esc_html(GHTK_PROVINCE_SPECIAL),
                    'ghtk_courier_code' => esc_html(GHTK_COURIER_CODE),
                    'ghn_courier_code' => esc_html(GHN_COURIER_CODE),
                    'sd_api_key' => esc_html(get_option('sd_api_key')),
                    'no_results' => esc_html__('Không tìm thấy kết quả.', 'ship-depot-translate')
                ),
                'all_provinces' => json_encode(Ship_Depot_Address_Helper::get_all_province(), JSON_UNESCAPED_UNICODE),
                'sd_locale' => esc_html(SHIP_DEPOT_LOCALE)
            ));
            wp_enqueue_script('sd-general-script', SHIP_DEPOT_DIR_URL . 'assets/js/general.js', array('jquery'), $this->version, false);
            wp_localize_script('sd-general-script', 'sd_general_params', array(
                'ajax' => array(
                    'url' => admin_url('admin-ajax.php'),
                ),
                'l10n' => array(
                    'loading' => esc_html__('Đang tải ...', 'ship-depot-translate'),
                    'calculating_shipping' => esc_html__('Đang tính phí ship ...', 'ship-depot-translate'),
                    'creating_order' => esc_html__('Đang tạo đơn hàng ...', 'ship-depot-translate'),
                    'processing' => esc_html__('Processing', 'ship-depot-translate'),
                    'create_order' => esc_html__('Tạo mới đơn hàng', 'ship-depot-translate'),
                    'create_order_again' => esc_html__('Tạo lại đơn hàng', 'ship-depot-translate'),
                    'create_order_successful' => esc_html__('Tạo mới đơn hàng thành công', 'ship-depot-translate'),
                    'select_ward' => esc_html(SD_SELECT_WARD_TEXT),
                    'select_district' => esc_html(SD_SELECT_DISTRICT_TEXT),
                    'ghtk_provice_spc' => esc_html(GHTK_PROVINCE_SPECIAL),
                    'no_results' => esc_html__('Không tìm thấy kết quả.', 'ship-depot-translate')
                ),
                'all_provinces' => json_encode(Ship_Depot_Address_Helper::get_all_province(), JSON_UNESCAPED_UNICODE),
                'sd_locale' => esc_html(SHIP_DEPOT_LOCALE)
            ));
        }
    }

    public function admin_enqueue_script()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        // Assets of woocommerce
        wp_enqueue_style('sd-bootstrap-style', SHIP_DEPOT_DIR_URL . 'assets/css/bootstrap/bootstrap.min.css', array(), '5.2.3', 'all');
        wp_enqueue_style('sd-main-style', SHIP_DEPOT_DIR_URL . 'assets/css/main.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-admin-style', SHIP_DEPOT_DIR_URL . 'assets/css/admin.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-admin-order-style', SHIP_DEPOT_DIR_URL . 'assets/css/admin-order.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-admin-settings-style', SHIP_DEPOT_DIR_URL . 'assets/css/admin-settings.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-admin-settings-couriers-style', SHIP_DEPOT_DIR_URL . 'assets/css/admin-settings-couriers.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-ship-station-style', SHIP_DEPOT_DIR_URL . 'assets/css/ship-station.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-admin-settings-fee-modify-style', SHIP_DEPOT_DIR_URL . 'assets/css/admin-settings-fee-modify.css', array(), '1.0.0', 'all');
        wp_enqueue_style('sd-dialog-style', SHIP_DEPOT_DIR_URL . 'assets/css/dialog.css', array(), '1.0.0', 'all');
        wp_enqueue_script('sd-bootstrap-script', SHIP_DEPOT_DIR_URL . 'assets/js/bootstrap/bootstrap.min.js', '5.2.3', false);
        wp_enqueue_script('sd-setting-script', SHIP_DEPOT_DIR_URL . 'assets/js/admin-settings.js', array('jquery'), $this->version, false);
        wp_enqueue_script('sd-couriers-script', SHIP_DEPOT_DIR_URL . 'assets/js/admin-settings-couriers.js', array('jquery'), $this->version, false);
        wp_enqueue_script('sd-ship-station-script', SHIP_DEPOT_DIR_URL . 'assets/js/admin-ship-station.js', array('jquery'), $this->version, false);
        wp_localize_script('sd-ship-station-script', 'sd_ship_station_params', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'ship_depot_host_api' => esc_url(SHIP_DEPOT_HOST_API)
            ),
            'l10n' => array(
                'loading' => esc_html__('Đang lấy danh sách bưu cục ...', 'ship-depot-translate'),
                'get_station_error' => esc_html__('Lấy danh sách bưu cục thất bại.', 'ship-depot-translate'),
                'select_station' => esc_html__('Chọn bưu cục', 'ship-depot-translate'),
            ),
            'error_messages' => array(
                'station_required' => esc_html__('Vui lòng chọn bưu cục. Nếu không có bưu cục nào thì hãy chọn vào ô Shipper lấy hàng tận nơi.', 'ship-depot-translate')
            ),
            'sd_api_key' => esc_html(get_option('sd_api_key')),
        ));
        wp_enqueue_script('sd-admin-order-detail-script', SHIP_DEPOT_DIR_URL . 'assets/js/admin-order-detail.js', array('jquery'), $this->version, false);
        wp_localize_script('sd-admin-order-detail-script', 'sd_order_detail_params', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'ship_depot_host_api' => esc_url(SHIP_DEPOT_HOST_API),
            ),
            'l10n' => array(
                'ghtk_hamlet_text' => esc_html__('Địa chỉ cấp 4 là', 'ship-depot-translate'),
                'change_text' => esc_html__('Thay đổi', 'ship-depot-translate'),
                'shipping_time_text' => esc_html__('T.gian nhận hàng ước tính:', 'ship-depot-translate'),
                'aha_shipping_time_text' => esc_html__('Thời gian di chuyển từ lúc lấy hàng:', 'ship-depot-translate'),
                'cod_fail_text' => esc_html__('Nếu khách hàng không nhận hàng, họ cần thanh toán phí ship là', 'ship-depot-translate'),
                'ghtk_ins_fee_text' => esc_html__('*Giao hàng tiết kiệm tự động tính phí bảo hiểm với đơn hàng có tổng giá trị sản phẩm lớn hơn 1 triệu đồng.', 'ship-depot-translate'),
            ),
            'error_messages' => array(
                'cancel_shipping' => esc_html__('Hủy vận đơn bị lỗi. Vui lòng thử lại sau.', 'ship-depot-translate'),
                'error_required' => esc_html__('Yêu cầu nhập đủ thông tin [param] để kiểm tra phí xử lý vận đơn.', 'ship-depot-translate'),
                'error_total' => esc_html__('Tính tổng cộng giá trị của đơn hàng bị lỗi. Vui lòng thử lại sau.', 'ship-depot-translate'),
                'package' => esc_html__('đóng gói', 'ship-depot-translate'),
                'receiver' => esc_html__('người nhận', 'ship-depot-translate'),
                'and' => esc_html__('và', 'ship-depot-translate'),
                'error_selected_shipping' => esc_html__('Yêu cầu chọn loại vận chuyển trước khi lưu.', 'ship-depot-translate')
            ),
            'sd_ghtk_code' => esc_html(GHTK_COURIER_CODE),
            'sd_ghn_code' => esc_html(GHN_COURIER_CODE),
            'sd_aha_code' => esc_html(AHA_COURIER_CODE),
            'sd_pas_code' => esc_html(PAS_COURIER_CODE),
            'sd_ghtk_province_special' => esc_html(GHTK_PROVINCE_SPECIAL),
            'sd_dir_url' => esc_url(SHIP_DEPOT_DIR_URL),
            'sd_api_key' => esc_html(get_option('sd_api_key')),
            'alert_messages' => array(
                'cancel_shipping' => esc_html__('Vận đơn đang trong quá trình thực hiện. Bạn muốn hủy vận đơn?', 'ship-depot-translate')
            )
        ));
        wp_enqueue_script('sd-fee-modify-script', SHIP_DEPOT_DIR_URL . 'assets/js/admin-fee-modify.js', array('jquery'), $this->version, false);
        wp_localize_script('sd-fee-modify-script', 'sd_fee_modify_params', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
            ),
            'error_messages' => array(
                'error_required' => esc_html__('Vui lòng điền thông tin vào các trường bắt buộc.', 'ship-depot-translate'),
                'error_compare' => esc_html__('Giá trị của trường Từ phải bé hơn giá trị của trường Đến.', 'ship-depot-translate')
            )
        ));
        wp_enqueue_script('sd-general-script', SHIP_DEPOT_DIR_URL . 'assets/js/general.js', array('jquery'), $this->version, false);
        wp_localize_script('sd-general-script', 'sd_general_params', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
            ),
            'l10n' => array(
                'loading' => esc_html__('Đang tải ...', 'ship-depot-translate'),
                'calculating_shipping' => esc_html__('Đang tính phí ship ...', 'ship-depot-translate'),
                'creating_order' => esc_html__('Đang tạo đơn hàng ...', 'ship-depot-translate'),
                'processing' => esc_html__('Processing', 'ship-depot-translate'),
                'create_order' => esc_html__('Tạo mới đơn hàng', 'ship-depot-translate'),
                'create_order_again' => esc_html__('Tạo lại đơn hàng', 'ship-depot-translate'),
                'create_order_successful' => esc_html__('Tạo mới đơn hàng thành công', 'ship-depot-translate'),
                'select_ward' => esc_html(SD_SELECT_WARD_TEXT),
                'select_district' => esc_html(SD_SELECT_DISTRICT_TEXT),
                'ghtk_provice_spc' => esc_html(GHTK_PROVINCE_SPECIAL),
                'no_results' => esc_html__('Không tìm thấy kết quả.', 'ship-depot-translate')
            ),
            'all_provinces' => json_encode(Ship_Depot_Address_Helper::get_all_province(), JSON_UNESCAPED_UNICODE),
            'sd_locale' => esc_html(SHIP_DEPOT_LOCALE)
        ));

        if (in_array($screen_id, array(
            'woocommerce_page_wc-settings',
            'shop_order',
            'woocommerce_page_wc-orders',
            'profile',
            'user-edit',
            'toplevel_page_friendstore'
        ))) {
            wp_enqueue_script('sd-admin-address', SHIP_DEPOT_DIR_URL . 'assets/js/admin-address.js', array('jquery'), $this->version, true);
            wp_localize_script('sd-admin-address', 'sd_admin_address_params', array(
                'ajax' => array(
                    'url' => admin_url('admin-ajax.php'),
                ),
                'l10n' => array(
                    'loading' => esc_html__('Đang tải ...', 'ship-depot-translate'),
                    'calculating_shipping' => esc_html__('Đang tính phí ship ...', 'ship-depot-translate'),
                    'creating_order' => esc_html__('Đang tạo đơn hàng ...', 'ship-depot-translate'),
                    'processing' => esc_html__('Processing', 'ship-depot-translate'),
                    'create_order' => esc_html__('Tạo mới đơn hàng', 'ship-depot-translate'),
                    'create_order_again' => esc_html__('Tạo lại đơn hàng', 'ship-depot-translate'),
                    'create_order_successful' => esc_html__('Tạo mới đơn hàng thành công', 'ship-depot-translate'),
                    'select_ward' => esc_html(SD_SELECT_WARD_TEXT),
                    'select_district' => esc_html(SD_SELECT_DISTRICT_TEXT),
                    'ghtk_provice_spc' => esc_html(GHTK_PROVINCE_SPECIAL),
                    'no_results' => esc_html__('Không tìm thấy kết quả.', 'ship-depot-translate')
                ),
                'all_provinces' => json_encode(Ship_Depot_Address_Helper::get_all_province(), JSON_UNESCAPED_UNICODE),
                'sd_locale' => esc_html(SHIP_DEPOT_LOCALE)
            ));
        }
    }

    public function hello_myfriend()
    {
        $items = '';
        $args_check = array(
            'language'      => array(
                'check' => array(get_option('WPLANG') => array('vi', 'vi-VN')),
                'label' => esc_html__('Language Vietnamese', 'ship-depot-translate'),
                'edit'  => admin_url('options-general.php#default_role'),
            ),
            'can_shipping'  => array(
                'check' => array(Ship_Depot_Address_Helper::can_shipping_vietnam() => true),
                'label' => esc_html__('Allow shipping to Vietnam', 'ship-depot-translate'),
                'edit'  => admin_url('admin.php?page=wc-settings#woocommerce_store_address'),
            ),
            'store_address' => array(
                'check' => array(
                    'city' . get_option('woocommerce_store_city')         => 'city',
                    'district' . get_option('woocommerce_store_district') => 'district',
                    'ward' . get_option('woocommerce_store_ward')         => 'ward'
                ),
                'label' => esc_html__('Update your store address', 'ship-depot-translate'),
                'edit'  => admin_url('admin.php?page=wc-settings#store_address-description'),
            ),
            'currency'      => array(
                'check' => array(
                    'currency' . get_woocommerce_currency()        => 'currencyVND',
                    'thousand' . wc_get_price_thousand_separator() => 'thousand.',
                    'decimal' . wc_get_price_decimal_separator()   => 'decimal,'
                ),
                'label' => esc_html__('Number format and currency of Vietnamese', 'ship-depot-translate'),
                'help'  => 'https://vi.wordpress.org/plugins/ship-depot-translate/#%C4%91%E1%BB%8Bnh%20d%E1%BA%A1ng%20s%E1%BB%91%20v%C3%A0%20%C4%91%C6%A1n%20v%E1%BB%8B%20ti%E1%BB%81n%20t%E1%BB%87%20vi%E1%BB%87t%20nam%20l%C3%A0%20g%C3%AC%3F',
                'edit'  => admin_url('admin.php?page=wc-settings#pricing_options-description'),
            ),
        );

        foreach ($args_check as $id => $check) {
            $pass = true;

            switch ($id) {
                case 'store_address':
                    foreach ($check['check'] as $key => $value) {
                        if ($key == $value) {
                            $pass = false;
                            break;
                        }
                    }
                    break;
                default:
                    foreach ($check['check'] as $key => $value) {
                        if ((is_array($value) && !in_array($key, $value)) ||
                            (!is_array($value) && $key != $value)
                        ) {
                            $pass = false;
                            break;
                        }
                    }
            }

            if (!$pass) {
                $items .= '<li id="' . $id . '">';
                $items .= $check['label'] . '.';

                if (isset($check['edit']) && $check['edit'])
                    $items .= ' <a href="' . $check['edit'] . '"><span class="dashicons dashicons-edit"></span></a>';

                if (isset($check['help']) && $check['help'])
                    $items .= ' <a href="' . $check['help'] . '" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>';

                $items .= '</li>';
            }
        }

        if ($items == '') return false;

        $message_html = '<p><strong>';
        $message_html .= sprintf(
            '%s %s',
            '<code class="error_content>' . esc_html__('Ship-Depot', 'ship-depot-translate') . '</code>',
            esc_html__('To Extension works, please config:', 'ship-depot-translate')
        );
        $message_html .= '</strong></p>';
        $message_html .= '<ol>' . $items . '</ol>';

        $notices = SD_Admin_Notices::get_instance();
        $notices->error(
            $message_html,
            'sd_config',
            array('woocommerce_page_wc-settings', 'options-general'),
            true
        );
    }

    public function no_woocommerce_keep_enabled()
    {
?>
        <div class="notice notice-error is-dismissible">
            <p><?php printf(
                    esc_html__('%s cần phải nâng cấp lên phiên bản mới nhất để tránh bị lỗi.', 'ship-depot-translate'),
                    '<strong>' . esc_html__('Ship Depot for WooCommerce', 'ship-depot-translate') . '</strong>',
                    '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank"><strong>WooCommerce</strong></a>'
                ); ?></p>
        </div>
        <?php
    }

    public function no_woocommerce_deactivated()
    {
        $update_plugins = get_site_transient('update_plugins');
        $plugin = 'ShipDepot/Ship_Depot_init.php';
        if (isset($update_plugins->response[$plugin])) {
            // Your plugin needs an update
        ?>
            <div class="vf-notice notice notice-error is-dismissible">
                <p style="color: #ff0000;">
                    <?php
                    $plugin_page = admin_url('plugins.php');
                    printf(
                        esc_html__('%s cần phải nâng cấp lên phiên bản mới nhất để tránh bị lỗi. Vui lòng vào trang %s tìm Ship Depot để nâng cấp.', 'ship-depot-translate'),
                        '<strong>' . esc_html__('Ship Depot for WooCommerce', 'ship-depot-translate') . '</strong>',
                        '<a href="' . esc_url($plugin_page) . '">Plugin<strong></strong></a>'
                    );
                    ?>

                </p>
            </div>
            <?php
        }
    }

    public function check_notify_update_plugin()
    {
        $update_plugins = get_site_transient('update_plugins');
        $plugin = 'Ship_Depot_init.php';
        foreach ($update_plugins->response as $key => $value) {
            if (str_contains($key, $plugin)) {
                // Your plugin needs an update
            ?>
                <div class="vf-notice notice notice-error is-dismissible">
                    <p style="color: #ff0000;">
                        <?php
                        $plugin_page = admin_url('plugins.php');
                        printf(
                            esc_html__('%s cần phải nâng cấp lên phiên bản mới nhất để tránh bị lỗi. Vui lòng vào trang %s tìm Ship Depot để nâng cấp.', 'ship-depot-translate'),
                            '<strong>' . esc_html__('Ship Depot for WooCommerce', 'ship-depot-translate') . '</strong>',
                            '<a href="' . esc_url($plugin_page) . '">Plugin<strong></strong></a>'
                        );
                        ?>

                    </p>
                </div>
        <?php
            }
        }
    }

    public function notify_switch_classic_checkout()
    { ?>
        <div class="vf-notice notice notice-error is-dismissible">
            <p>
                <?php
                printf(
                    esc_html__('%s cần phải chỉnh về classic checkout để tránh bị lỗi. Vui lòng vào %s để xem hướng dẫn.', 'ship-depot-translate'),
                    '<strong>' . esc_html__('Ship Depot for WooCommerce', 'ship-depot-translate') . '</strong>',
                    '<a href="' . esc_url(get_site_url() . '/wp-admin/admin.php?page=wc-settings&tab=sd_settings&section=classic_checkout_direction') . '">đây<strong></strong></a>'
                );
                ?>

            </p>
        </div>
    <?php
    }

    public function auto_deactivate()
    {
    ?>
<?php
        $vf_plugin = '';
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins', array());
            $fl_array = preg_grep("/Ship_Depot_init.php$/", array_keys($plugins));
            $vf_plugin = reset($fl_array);
        } else {
            $plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
            $fl_array = preg_grep("/Ship_Depot_init.php$/", $plugins);
            if (count($fl_array) > 0) {
                $vf_plugin = $plugins[key($fl_array)];
            }
        }
        if (!empty($vf_plugin)) {
            deactivate_plugins($vf_plugin);

            // Hide the default "Plugin activated" notice
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    public function sd_http_request_timeout_extend($time)
    {
        return 60;
    }
}
