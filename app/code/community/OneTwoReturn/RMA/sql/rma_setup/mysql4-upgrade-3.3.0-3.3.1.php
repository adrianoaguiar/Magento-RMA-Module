<?php
$installer = $this;
$installer->startSetup();

$rmaTable           = $installer->getTable('sales_flat_order_rma');
        
$installer->getConnection()->addColumn($rmaTable, 'rma_sales_rule_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length'    => 12,
        'nullable'  => false,
        'default'   => 0,
        'comment'   =>'Rule ID for coupon'
        ));
        
        
$installer->endSetup();
?>