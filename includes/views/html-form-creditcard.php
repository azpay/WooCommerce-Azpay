<?php
if (!defined('ABSPATH'))
	exit; // Exit if accessed directly.

wp_enqueue_style('creditcard', plugins_url( '../assets/css/creditcard.css', plugin_dir_path( __FILE__ ) ) );
wp_enqueue_script('creditCardValidator', plugins_url( '../assets/js/jquery.creditCardValidator.js', plugin_dir_path( __FILE__ ) ) );
wp_enqueue_script('mask', plugins_url( '../assets/js/jquery.maskedinput.min.js', plugin_dir_path( __FILE__ ) ) );
wp_enqueue_script('creditcard', plugins_url( '../assets/js/creditcard.js', plugin_dir_path( __FILE__ ) ) );
?>

<section class="azpay-lite-credit-card">
        <p><?php echo $this->creditcard_form_description; ?></p>

        <ul class="inputs">
        	<li class="input-flags">
        		<label for="azpaylte_cc_form_flag">Bandeira</label>
        		<br>
        		
        		<?php if ($this->visa_acquirer != 0 && $this->validate_parcel($cart_total, 'visa')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_visa" value="visa" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'visa');?>"><img src="<?php echo plugins_url('../assets/img/visa.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->mastercard_acquirer != 0 && $this->validate_parcel($cart_total, 'mastercard')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_mastercard" value="mastercard" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'mastercard');?>"><img src="<?php echo plugins_url('../assets/img/mastercard.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->diners_acquirer != 0 && $this->validate_parcel($cart_total, 'diners')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_diners" value="diners" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'diners');?>"><img src="<?php echo plugins_url('../assets/img/diners.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->amex_acquirer != 0 && $this->validate_parcel($cart_total, 'amex')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_amex" value="amex" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'amex');?>"><img src="<?php echo plugins_url('../assets/img/amex.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->elo_acquirer != 0 && $this->validate_parcel($cart_total, 'elo')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_elo" value="elo" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'elo');?>"><img src="<?php echo plugins_url('../assets/img/elo.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->aura_acquirer != 0 && $this->validate_parcel($cart_total, 'aura')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_aura" value="aura" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'aura');?>"><img src="<?php echo plugins_url('../assets/img/aura.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->discover_acquirer != 0 && $this->validate_parcel($cart_total, 'discover')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_discover" value="discover" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'discover');?>"><img src="<?php echo plugins_url('../assets/img/discover.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->jcb_acquirer != 0 && $this->validate_parcel($cart_total, 'jcb')): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-cc-form-flag" name="azpaylte_cc_form_flag" id="azpaylte_cc_form_jcb" value="jcb" data-mask="9999 9999 9999 9999" data-parcels="<?php echo $this->parcel_qnt($cart_total, 'jcb');?>"><img src="<?php echo plugins_url('../assets/img/jcb.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        	</li>

        	<li>
                <label>Parcelamento</label>
                <select name="azpaylte_cc_form_parcel" class="azpaylte-cc-form-parcel" id="azpaylte_cc_form_parcel"></select>
            </li>

            <li>
                
                <div class="display-block">
                	<label>Nome no Cartão</label>
                	<input type="text" name="azpaylte_cc_form_name" value="Nome no cartão" class="azpaylte-cc-form-name" required />
                </div>

                <div class="display-block">                	
		            <label>Número do Cartão</label>
		            <input type="text" name="azpaylte_cc_form_number" value="4000000000010001" class="azpaylte-cc-form-number" required />		            
                </div>

            </li>

            
            <li>
               <div class="display-block">
	               	<label>Data de Validate</label>
	                <input type="text" name="azpaylte_cc_form_validate" value="05/2018" size="10" class="azpaylte-cc-form-validate" required />
               </div>
               <div class="display-block">
	               	<label>Código de Segurança (CVC)</label>
                	<input type="text" name="azpaylte_cc_form_cvv" value="123" size="10" class="azpaylte-cc-form-cvv" required />
               </div>               
            </li>
        </ul>
</section>
