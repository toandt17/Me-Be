<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_ResultDTO')) {
    class Ship_Depot_ResultDTO
    {
        public int $Code = 0;
        public string $Message = "";

        function __construct($code, $message){
            $this->Code = $code;
            $this->Message = $message;
        }
    }
}