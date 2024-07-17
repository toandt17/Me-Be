<?php
if (!defined('WPINC')) {
    die;
}

$content = file_get_contents(SHIP_DEPOT_DIR_URL . 'assets/css/fe-checkout-custom.css');
$textarea_id = "fancy-textarea";
?>

<style type="text/css">
    .CodeMirror {
        border: 1px solid #ddd;
        min-height: 50vh;
    }

    #btn_restore_css {
        margin-top: 10px;
    }
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        editorSettings.codemirror = _.extend({},
            editorSettings.codemirror, {
                indentUnit: 2,
                tabSize: 2,
                mode: 'css',
            }
        );
        var cm_editor = wp.codeEditor.initialize($('#<?php echo $textarea_id; ?>'), editorSettings);
        $(document).on('keyup', '.CodeMirror-code', function() {
            $('#<?php echo $textarea_id; ?>').html(cm_editor.codemirror.getValue());
        });

        $('#btn_restore_css').click(function() {
            $('#sd_restore_css').val('true');
            $('button[type=submit][name=save]').trigger('click');
        })
    })
</script>

<?php
wp_nonce_field('sd_custom_css', 'sd_custom_css_nonce');
?>
<div class="sd-div sd-custom-css">
    <b>CSS trang Thanh toán</b>
    <p><?php esc_html_e('Thay đổi CSS của Ship Depot tại trang Thanh toán.', 'ship-depot-translate') ?></p>
    <textarea id="<?php echo $textarea_id; ?>" name="sd_checkout_custom_css"><?php echo esc_textarea($content) ?></textarea>
    <div class="notice notice-warning inline active-plugin-edit-warning">
        <p><strong><?php esc_html_e('Lưu ý:', 'ship-depot-translate') ?></strong> <?php esc_html_e('Thay đổi CSS cần người có kiến thức chuyên môn.', 'ship-depot-translate') ?></p>
    </div>
    <a class="button-a" id="btn_restore_css">
        <?php esc_html_e('Khôi phục về CSS mặc định', 'ship-depot-translate') ?>
    </a>
    <input type="hidden" name="sd_restore_css" id="sd_restore_css" value="false" />
</div>