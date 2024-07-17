<?php
class ExtraShippingStatus
{
    public function __construct()
    {
        $this->add_custom_order_status();
    }

    function add_custom_order_status()
    {
        add_action('init', array($this, 'ship_depot_register_custom_status'));
        add_filter('wc_order_statuses', array($this, 'ship_depot_add_status_to_list'));
    }



    function get_list_custom_order_statuses()
    {
        return [
            'wc-sd-delivering' => __('Đang giao hàng', 'ship-depot-translate'),
            'wc-sd-delivered' => __('Đã giao hàng', 'ship-depot-translate'),
            'wc-sd-delivery-failed' => __('Giao hàng thất bại', 'ship-depot-translate')
        ];
    }



    function ship_depot_register_custom_status()
    {
        foreach ($this->get_list_custom_order_statuses() as $id => $status) {
            register_post_status(
                $id,
                array(
                    'label'        => $status,
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'    => _n_noop($status . ' <span class="count">(%s)</span>', $status . ' <span class="count">(%s)</span>')
                )
            );
        }
    }

    function ship_depot_add_status_to_list($order_statuses)
    {
        $arrange_default_statuses = array();
        //Arrange default status: move status 'failed' from end to next to completed
        foreach ($order_statuses as $key => $status) {
            if ('wc-failed' !== $key) {
                $arrange_default_statuses[$key] = $status;
            }
            if ('wc-completed' === $key) {
                $arrange_default_statuses['wc-failed'] = $order_statuses['wc-failed'];
            }
        }
        $new_order_statuses = array();
        //Add custom status
        $list_custom_statuses = $this->get_list_custom_order_statuses();
        foreach ($arrange_default_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-on-hold' === $key) {
                foreach ($list_custom_statuses as $id => $stt) {
                    $new_order_statuses[$id] = $stt;
                    if ('wc-sd-delivered' === $id) {
                        break;
                    }
                }
            } else if ('wc-failed' === $key) {
                $new_order_statuses['wc-sd-delivery-failed'] = $list_custom_statuses['wc-sd-delivery-failed'];
            }
        }
        return $new_order_statuses;
    }
}

new ExtraShippingStatus();
