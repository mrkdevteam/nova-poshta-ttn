<?php

use plugins\NovaPoshta\classes\invoice\Sender;
use plugins\NovaPoshta\classes\invoice\Recipient;

$sender = Sender::getInstance();
$recipient = new Recipient();
?>
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="<?php echo PLUGIN_URL; ?>public/css/style.css?ver=<?php echo MNP_PLUGIN_VERSION; ?>"/>
<?php

$invoiceModel->displayNav(); ?>

<div class="container mrkvnp_invoice">
    <h2 class="np_order_data_h2">Замовлення № <?php echo $order_data['id'];  ?></h2>
    <div id="messageboxnp" class="messagebox_show"></div>
    <div class="alink">
        <a class="btn" href="/wp-admin/post.php?post=<?php echo $order_id ?>&action=edit">Повернутись до замовлення</a>
        <a href="edit.php?post_type=shop_order" >Повернутись до замовлень</a>
    </div>
