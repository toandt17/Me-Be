<?php
// don't load directly
defined('ABSPATH') || die('-1');

if (!class_exists('VF_Admin_Notices')) {

    class SD_Admin_Notices
    {

        private static $_instance;
        private $admin_notices;
        const TYPES = 'error,warning,info,success';
        const KEY_META = 'vfnotices_ignore';

        private function __construct()
        {
            $this->admin_notices = new stdClass();
            foreach (explode(',', self::TYPES) as $type) {
                $this->admin_notices->{$type} = array();
            }
            add_action('admin_init', array(&$this, 'action_admin_init'));
            add_action('admin_notices', array(&$this, 'action_admin_notices'));
            add_action('admin_enqueue_scripts', array(&$this, 'action_admin_enqueue_scripts'));
        }

        public static function get_instance()
        {
            if (!(self::$_instance instanceof self)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function action_admin_init()
        {
            $notice_id = filter_input(INPUT_GET, 'vfnotice', FILTER_SANITIZE_STRING);
            if (is_string($notice_id)) {
                global $current_user;
                $user_id = $current_user->ID;

                $notice_ignore = get_user_meta($user_id, self::KEY_META, true);
                if ($notice_ignore == null) $notice_ignore = array();
                $notice_ignore[$notice_id] = true;
                update_user_meta($user_id, self::KEY_META, $notice_ignore, false);
                wp_die('Dismiss this notice successfully!', 'Success!', array('response' => 200));
            }
        }

        public function action_admin_enqueue_scripts()
        {
            wp_enqueue_script('sd-admin-notices', SHIP_DEPOT_DIR_URL . 'assets/js/admin-notices.js', array('jquery'), SHIP_DEPOT_VERSION, true);
        }

        public function action_admin_notices()
        {
            $screen_id = get_current_screen() ? get_current_screen()->id : '';
            $friendstore_notice = get_user_meta(get_current_user_id(), self::KEY_META, true);

            foreach (explode(',', self::TYPES) as $type) {
                foreach ($this->admin_notices->{$type} as $admin_notice) {
                    $notice_id = $admin_notice->id;
                    $notice_dismiss = $admin_notice->dismiss ? 'is-dismissible' : '';
                    $notice_dismiss_url = add_query_arg(array('vfnotice' => $notice_id), admin_url());
                    $notice_display = false;

                    $dismiss_url = $admin_notice->dismiss ? 'data-dismiss-url="' . esc_url($notice_dismiss_url) . '"' : '';

                    // Always show notices
                    if (count($admin_notice->screens) > 0 && in_array($screen_id, $admin_notice->screens)) {
                        $notice_dismiss = '';
                        $dismiss_url = '';
                        $notice_display = true;
                    }

                    if (isset($friendstore_notice[$notice_id]) && $friendstore_notice[$notice_id] && !$notice_display) return;

                    echo "<div id='" . esc_attr($notice_id) . "' class='vf-notice notice notice-" . esc_attr($type) . " " .esc_attr($notice_dismiss)."' {$dismiss_url}>";
                    echo esc_html($admin_notice->message);
                    echo "</div>";
                }
            }
        }

        public function error($message, $id = '', $screens = array(), $dismiss = false)
        {
            $this->notice('error', $message, $id, $screens, $screens);
        }

        public function warning($message, $id = '', $screens = array(), $dismiss = false)
        {
            $this->notice('warning', $message, $id, $screens, $screens);
        }

        public function success($message, $id = '', $screens = array(), $dismiss = false)
        {
            $this->notice('success', $message, $id, $screens, $screens);
        }

        public function info($message, $id = '', $screens = array(), $dismiss = false)
        {
            $this->notice('info', $message, $id, $screens, $screens);
        }

        private function notice($type, $message, $id, $screens, $dismiss)
        {
            $notice = new stdClass();
            $notice->id = $id;
            $notice->message = $message;
            $notice->screens = $screens;
            $notice->dismiss = $dismiss;

            $this->admin_notices->{$type}[] = $notice;
        }

        public static function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
        {
            if (!(error_reporting() & $errno)) {
                // This error code is not included in error_reporting
                return;
            }

            $message = "errstr: $errstr, errfile: $errfile, errline: $errline, PHP: " . PHP_VERSION . " OS: " . PHP_OS;

            $self = self::get_instance();

            switch ($errno) {
                case E_USER_ERROR:
                    $self->error($message);
                    break;

                case E_USER_WARNING:
                    $self->warning($message);
                    break;

                case E_USER_NOTICE:
                default:
                    $self->notice('notice', $message, '', array(), false);
                    break;
            }

            // write to wp-content/debug.log if logging enabled
            error_log($message);

            // Don't execute PHP internal error handler
            return true;
        }
    }
}
