jQuery(document).ready(function(){

    jQuery("body").on('ajaxComplete', function(){

        jQuery(".azpaylte-cc-form-name").val('');
        jQuery(".azpaylte-cc-form-number").val('');
        jQuery(".azpaylte-cc-form-validate").val('');
        jQuery(".azpaylte-cc-form-cvv").val('');

        payment_method = jQuery('input[name="payment_method"]:checked').val();

        jQuery(".azpaylte-cc-form-flag").eq(0).prop("checked", true);
        jQuery(".azpaylte-cc-form-validate").mask("99/99");
        numberMask();
        mountSelect();
        cvvMask();

    });


    /**
     * On change flag
     */
    jQuery("body").on("click", ".azpaylte-cc-form-flag", function(){
        mountSelect();
        numberMask();
        cvvMask();
    });


    /**
     * OnClick to pay
     */
    jQuery("body").on("click", "#place_order", function(e){        

        payment_method = jQuery('input[name="payment_method"]:checked').val();

        // If select CreditCard payment method, validate data
        if (payment_method == 'azpay_lite_creditcard') {
            e.preventDefault();

            flag = jQuery(".azpaylte-cc-form-flag:checked").val();
            name = jQuery(".azpaylte-cc-form-name").val();
            number = jQuery(".azpaylte-cc-form-number").val();
            cvv = jQuery(".azpaylte-cc-form-cvv").val();
            validate = jQuery(".azpaylte-cc-form-validate").val();

            if (name.length == 0) {
                alert('Preencha o nome que está no cartão');
                return;
            }

            if (number.length == 0) {
                alert('Preencha o número do cartão');
                return;
            }

            if (cvv.length == 0) {
                alert('Preencha o código de segurança do cartão');
                return;
            }

            if (validate.length == 0) {
                alert('Preencha a data de expiração do cartão');
                return;
            }

            validate_card = jQuery(".azpaylte-cc-form-number").validateCreditCard({accept: [flag]});

            if (validate_card.valid && validate_card.length_valid && validate_card.luhn_valid) {
                jQuery(".woocommerce-checkout").submit();
            } else {
                alert("Número do cartão inválido");
            }
        }

    });


    /**
     * Mount the select e reload
     */
    function mountSelect() {

        parcels = jQuery(".azpaylte-cc-form-flag:checked").data("parcels");
        $select_parcels = jQuery(".azpaylte-cc-form-parcel");
        $select_parcels.empty();

        for (var i=1; i<=parcels; i++) {
            option = jQuery('<option></option>').attr("value", i).text(i + "x");
            $select_parcels.append(option);
        };
    }

    /**
     * Mask of number
     * based on flag
     * @return {[type]} [void]
     */
    function numberMask() {
        jQuery(".azpaylte-cc-form-number").mask(jQuery(".azpaylte-cc-form-flag:checked").data('mask'));
    }

    /**
     * Mask of CVV code
     * @return {[type]}      [void]
     */
    function cvvMask() {
        if (jQuery(".azpaylte-cc-form-flag:checked").val() === "amex") {
            jQuery(".azpaylte-cc-form-cvv").mask("9999");
        } else {
            jQuery(".azpaylte-cc-form-cvv").mask("999");
        }
    }

});
