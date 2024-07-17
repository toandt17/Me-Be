/**
 * Admin
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.1.0
 */
jQuery(function($){
    /** ------------------------------------------------------------------------
     *  Settings clear terms value when add a new term on attributes page
     * ------------------------------------------------------------------------- */
    $('#submit').on( 'click', function(){
        if ( $('#addtag').length ) {
            setTimeout(function(){
                //Set it as default.
                $('input[type="checkbox"]').prop( "checked", false );
                $('.yith-plugin-fw-media .yith-plugin-fw-media__preview__action--delete').trigger('click');
                $('.wp-picker-default').trigger('click');
            }, 300);

        }
    });
});