<?php
if (!defined('ABSPATH'))
	exit; // Exit if accessed directly.

?>

<section class="azpay-lite-boleto">
    <p><?php echo $this->boleto_form_description; ?></p>
    <p>Desconto de <?php echo $this->boleto_discount.'%' ?> (R$ <?php echo number_format($this->get_discount($cart_total), 2, ',', '.');?>)</p>
    <p>Total: R$ <?php echo number_format($this->apply_discount($cart_total), 2, ',', '.'); ?></p>
</section>
