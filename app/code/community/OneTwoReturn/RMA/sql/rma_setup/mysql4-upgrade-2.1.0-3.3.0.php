<?php
$installer = $this;
$installer->startSetup();

$rmaTable			= $installer->getTable('sales_flat_order_rma');

$installer->getConnection()->addColumn($rmaTable, 'rma_refund_method', array(
		'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length'    => 1,
        'nullable'  => false,
        'default' 	=> 0,
        'comment'	=>'Indicator for refund method'
        ));
        
		
$installer->getConnection()->addColumn($rmaTable, 'rma_withdrawal_form', array(
		'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length'    => 1,
        'nullable'  => false,
        'default' 	=> 0,
        'comment'	=>'Indicator for withdrawal form'
        ));
		
$installer->endSetup();
?>