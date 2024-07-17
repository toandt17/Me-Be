<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Customer')) {
    class Ship_Depot_Customer extends Ship_Depot_Base_Model
    {
        public string $FirstName = "";
        public string $LastName = "";
        public string $Province = "";
        public string $District = "";
        public string $Ward = "";
        public string $Address = "";
        //For GHTK: Hamlet = địa chỉ cấp 4 - Tên thôn/ấp/xóm/tổ/… của người nhận hàng hóa. Nếu không có, vui lòng điền “Khác”
        public string $Hamlet = "";
        //
        public string $Phone = "";
        public string $Email = "";
    }
}
