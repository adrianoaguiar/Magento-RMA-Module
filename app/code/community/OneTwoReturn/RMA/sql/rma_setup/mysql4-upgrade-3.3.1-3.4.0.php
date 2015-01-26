<?php
$installer = $this;
$installer->startSetup();

$rmaItemsTable      = $installer->getTable('sales_flat_order_rma_items');
        
$installer->getConnection()->addColumn($rmaItemsTable, 'exchange_pref', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => false,
        'default'   => 0,
        'comment'   =>'If the customer want to exchange, save here his preference'
        ));
$installer->endSetup();
?>