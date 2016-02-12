jQuery(document).ready(function($){

    $("body").on('ajaxComplete', function(){

        $(".azpaylte-cc-form-name").val('');
        $(".azpaylte-cc-form-number").val('');
        $(".azpaylte-cc-form-validate").val('');
        $(".azpaylte-cc-form-cvv").val('');

        payment_method = $('input[name="payment_method"]:checked').val();

        $(".azpaylte-cc-form-flag").eq(0).prop("checked", true);
        $(".azpaylte-cc-form-validate").mask("99/9999");
        numberMask();
        mountSelect();
        cvvMask();

    });


    /**
     * On change flag
     */
    $("body").on("click", ".azpaylte-cc-form-flag", function(){
        mountSelect();
        numberMask();
        cvvMask();
    });


    /**
     * OnClick to pay
     */
    $("body").on("click", "#place_order", function(e){        

        payment_method = $('input[name="payment_method"]:checked').val();

        // If select CreditCard payment method, validate data
        if (payment_method == 'azpay_lite_creditcard') {
            e.preventDefault();

            flag = $(".azpaylte-cc-form-flag:checked").val();
            name = $(".azpaylte-cc-form-name").val();
            number = $(".azpaylte-cc-form-number").val();
            cvv = $(".azpaylte-cc-form-cvv").val();
            validate = $(".azpaylte-cc-form-validate").val();

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

            validate_card = $(".azpaylte-cc-form-number").validateCreditCard({accept: [flag]});

            if (validate_card.valid && validate_card.length_valid && validate_card.luhn_valid) {
                $(".woocommerce-checkout").submit();
            } else {
                alert("Número do cartão inválido");
            }
        }

    });


    /**
     * Mount the select e reload
     */
    function mountSelect() {

        parcels = $(".azpaylte-cc-form-flag:checked").data("parcels");
        $select_parcels = $(".azpaylte-cc-form-parcel");
        $select_parcels.empty();

        for (var i=1; i<=parcels; i++) {
            option = $('<option></option>').attr("value", i).text(i + "x");
            $select_parcels.append(option);
        };
    }

    /**
     * Mask of number
     * based on flag
     * @return {[type]} [void]
     */
    function numberMask() {
        $(".azpaylte-cc-form-number").mask($(".azpaylte-cc-form-flag:checked").data('mask'));
    }

    /**
     * Mask of CVV code
     * @return {[type]}      [void]
     */
    function cvvMask() {
        if ($(".azpaylte-cc-form-flag:checked").val() === "amex") {
            $(".azpaylte-cc-form-cvv").mask("9999");
        } else {
            $(".azpaylte-cc-form-cvv").mask("999");
        }
    }

});