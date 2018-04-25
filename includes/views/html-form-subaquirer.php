<?php
if (!defined('ABSPATH'))
	exit; // Exit if accessed directly.

wp_enqueue_style('debitcard', plugins_url( '../assets/css/debitcard.css', plugin_dir_path( __FILE__ ) ) );
wp_enqueue_script('creditCardValidator', plugins_url( '../assets/js/jquery.creditCardValidator.js', plugin_dir_path( __FILE__ ) ) );
wp_enqueue_script('mask', plugins_url( '../assets/js/jquery.maskedinput.min.js', plugin_dir_path( __FILE__ ) ) );
wp_enqueue_script('creditcard', plugins_url( '../assets/js/debitcard.js', plugin_dir_path( __FILE__ ) ) );
?>

<section class="azpay-lite-subacquirer">
        <p><?php echo $this->subacquirer_form_description; ?></p>

        <ul class="inputs">
        	<li class="input-flags">
        		<label for="azpaylte_dc_form_flag">Bandeira</label>
        		<br>
        		
        		<?php if ($this->visa_acquirer != 0): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-dc-form-flag" name="azpaylte_dc_form_flag" id="azpaylte_dc_form_visa" value="visa" data-mask="9999 9999 9999 9999" data-flag="visa_electron"><img src="<?php echo plugins_url('../assets/img/visa.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        		<?php if ($this->mastercard_acquirer != 0): ?>
        			<div class="input-flag">
        				<label><input type="radio" class="azpaylte-dc-form-flag" name="azpaylte_dc_form_flag" id="azpaylte_dc_form_mastercard" value="mastercard" data-mask="9999 9999 9999 9999" data-flag="maestro"><img src="<?php echo plugins_url('../assets/img/mastercard.jpg', plugin_dir_path( __FILE__ )); ?>" /></label>
        			</div>
        		<?php endif ?>

        	</li>

            <li>
                
                <div class="display-block">
                	<label>Nome no Cartão</label>
                	<input type="text" name="azpaylte_dc_form_name" value="" class="azpaylte-dc-form-name" />
                </div>

                <div class="display-block">                	
		            <label>Número do Cartão</label>
		            <input type="text" name="azpaylte_dc_form_number" value="" class="azpaylte-dc-form-number" />		            
                </div>

            </li>

            
            <li>
               <div class="display-block">
	               	<label>Data de Validate</label>
	                <input type="text" name="azpaylte_dc_form_validate" value="" size="10" class="azpaylte-dc-form-validate" />
               </div>
               <div class="display-block">
	               	<label>Código de Segurança (CVC)</label>
                	<input type="text" name="azpaylte_dc_form_cvv" value="" size="10" class="azpaylte-dc-form-cvv" />
               </div>               
            </li>
        </ul>
</section>
