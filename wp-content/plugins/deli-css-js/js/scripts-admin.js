jQuery(document).ready(function($){


    //  btn TOGGLER
    $(document).on('click','.bt-toggler', function(e){
        e.preventDefault();
        //
        var cible = $(this).attr("href");
        // $(cible).stop().fadeToggle();
        $(cible).stop().animate({'height':'toggle'});
    });



    // DATE PICKER
    // Check to make sure the input box exists
    if( 0 < $('.js-datepicker-defaut').length ) {
        $('.js-datepicker-defaut').datepicker({dateFormat: "mm/dd/yy" } );

    } // end if


}); // end ready();
