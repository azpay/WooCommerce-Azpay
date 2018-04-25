jQuery(document).ready(function($){

    $("body").on('ajaxComplete', function(){

        $(".azpaylte-dc-form-name").val('');
        $(".azpaylte-dc-form-number").val('');
        $(".azpaylte-dc-form-validate").val('');
        $(".azpaylte-dc-form-cvv").val('');

        payment_method = $('input[name="payment_method"]:checked').val();

        $(".azpaylte-dc-form-flag").eq(0).prop("checked", true);
        $(".azpaylte-dc-form-validate").mask("99/9999");
        numberMask();
        mountSelect();
        cvvMask();

    });


    /**
     * On change flag
     */
    $("body").on("click", ".azpaylte-dc-form-flag", function(){
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
        if (payment_method == 'azpay_lite_debitcard') {
            e.preventDefault();

            flag = $(".azpaylte-dc-form-flag:checked").val();
            flag_validation = $(".azpaylte-dc-form-flag:checked").data("flag");
            name = $(".azpaylte-dc-form-name").val();
            number = $(".azpaylte-dc-form-number").val();
            cvv = $(".azpaylte-dc-form-cvv").val();
            validate = $(".azpaylte-dc-form-validate").val();

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

            validate_card = $(".azpaylte-dc-form-number").validateCreditCard({accept: [flag_validation]});

            if (validate_card.valid && validate_card.length_valid && validate_card.luhn_valid) {
                $(".woocommerce-checkout").submit();
            } else {
                alert("Número do cartão inválido");
            }
        }

    });


    /**
     * Mask of number
     * based on flag
     * @return {[type]} [void]
     */
    function numberMask() {
        $(".azpaylte-dc-form-number").mask($(".azpaylte-dc-form-flag:checked").data('mask'));
    }

});
