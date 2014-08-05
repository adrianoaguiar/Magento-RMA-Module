<?php

$installer = $this;
$installer->startSetup();

/*
 * Adding RMA order status & link to order state
 */
 
$orderTable 		= $installer->getTable('sales/order');
$statusTable        = $installer->getTable('sales/order_status');
$statusStateTable   = $installer->getTable('sales/order_status_state');
$statusLabelTable   = $installer->getTable('sales/order_status_label');
$rmaTable			= $installer->getTable('sales_flat_order_rma');
$rmaItemsTable		= $installer->getTable('sales_flat_order_rma_items');
$rmaConditionsTable	= $installer->getTable('sales_flat_order_rma_conditions');
$rmaAddressTable	= $installer->getTable('sales_flat_order_rma_address');
$rmaReutilTable		= $installer->getTable('sales_flat_order_rma_reutil');
$rmaStatusTable		= $installer->getTable('sales_order_rma_status');

$data = array(
    array('status' => 'rma_cancelled', 'label' => 'RMA Cancelled')
);
$installer->getConnection()->insertArray($statusTable, array('status', 'label'), $data);

$data = array(
    array('status' => 'rma_cancelled', 'state' => 'complete', 'is_default' => 0)
);
$installer->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $data);


$table = $installer->getConnection()->newTable($rmaTable)
    ->addColumn('rma_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'ID')
    ->addColumn('rma_reference', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'nullable'  => false,
        ), 'RMA reference')
	->addColumn('rma_context', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'Return type')
	->addColumn('rma_ip', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'Placed from IP')
	->addColumn('rma_store_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 20, array(
        'nullable'  => false,
        ), 'Placed from Store')
	->addColumn('rma_createdate', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, 0, array(
        'nullable'  => false,
        ), 'RMA creation date')
	->addColumn('rma_updatedate', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, 0, array(
        'nullable'  => false,
        ), 'RMA update date')
	->addColumn('rma_order_increment_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
        'nullable'  => false,
        ), 'Order number')	
	->addColumn('rma_order_entity_id', Varien_Db_Ddl_Table::TYPE_BIGINT , 12, array(
        'nullable'  => false,
        ), 'Order entity id')
	->addColumn('rma_customer_id', Varien_Db_Ddl_Table::TYPE_BIGINT , 12, array(
        'nullable'  => false,
        ), 'Order entity id')
	->addColumn('rma_status_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA status')
	->addColumn('rma_labellink', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA label link')
	->addColumn('rma_language', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA language')
	->addColumn('rma_returnprompt', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA shipper prompt')
	->addColumn('rma_returnlogo', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA shipper logo')
	->addColumn('rma_returnurl', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA shipper url')
	->addColumn('rma_returninstruction', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        ), 'RMA shipper instructions');	
		
$installer->getConnection()->createTable($table);


$table2 = $installer->getConnection()->newTable($rmaItemsTable)
    ->addColumn('rma_items_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'ID')
	->addColumn('rma_items_lineno', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'nullable'  => false,
        ), 'Order line')
	->addColumn('rma_items_product_model', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'Product id')
	->addColumn('rma_items_rma_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'nullable'  => false,
        ), 'RMA id')
    ->addColumn('rma_items_order_item_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'nullable'  => false,
        ), 'Order item id')
	->addColumn('rma_items_order_paren_item_id', Varien_Db_Ddl_Table::TYPE_BIGINT, 12, array(
        'nullable'  => false,
        ), 'Order parent item id')	
	->addColumn('rma_items_rma_status_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'Order item id')
	->addColumn('rma_items_qty_returning', Varien_Db_Ddl_Table::TYPE_INTEGER, 5, array(
        'nullable'  => false,
        ), 'RMA items returning')
	->addColumn('rma_items_qty_returned', Varien_Db_Ddl_Table::TYPE_INTEGER, 5, array(
        'nullable'  => false,
        ), 'RMA items returned')
	->addColumn('rma_items_qty_tocredit', Varien_Db_Ddl_Table::TYPE_INTEGER, 5, array(
        'nullable'  => false,
        ), 'RMA items refunded')
	->addColumn('rma_items_qty_tostock', Varien_Db_Ddl_Table::TYPE_INTEGER, 5, array(
        'nullable'  => false,
        ), 'RMA items restocked')
	->addColumn('rma_items_createdate', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, 0, array(
        'nullable'  => false,
        ), 'RMA items creation date')
	->addColumn('rma_items_updatedate', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, 0, array(
        'nullable'  => false,
        ), 'RMA items update date');
$installer->getConnection()->createTable($table2);

$table3 = $installer->getConnection()->newTable($rmaStatusTable)
    ->addColumn('rma_status_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 4, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'ID')
    ->addColumn('rma_status_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA status code')
	->addColumn('rma_status_label', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA status label')
	->addColumn('rma_status_alias', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA status match');
$installer->getConnection()->createTable($table3);

$table4 = $installer->getConnection()->newTable($rmaConditionsTable)
    ->addColumn('rma_conditions_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'RMA Conditions ID')
	->addColumn('rma_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'nullable'  => false,
        ), 'RMA ID')
    ->addColumn('rma_conditions_questioncode', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA Question ID')
	->addColumn('rma_conditions_questiondesc', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA question')
	->addColumn('rma_conditions_itemcode', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA answer code')
	->addColumn('rma_conditions_itemdesc', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA answer');
$installer->getConnection()->createTable($table4);

$table5 = $installer->getConnection()->newTable($rmaAddressTable)
    ->addColumn('rma_address_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'RMA Address ID')
	->addColumn('rma_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'nullable'  => false,
        ), 'RMA ID')
    ->addColumn('rma_customer_identifier', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer ID')
	->addColumn('rma_customer_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer name')
	->addColumn('rma_customer_street', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer street')
	->addColumn('rma_customer_houseno', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer houseno')
	->addColumn('rma_customer_postal', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer postal')
	->addColumn('rma_customer_city', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer city')
	->addColumn('rma_customer_country', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer country')
	->addColumn('rma_customer_residential', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer residental')
	->addColumn('rma_customer_phone', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer phone')
	->addColumn('rma_customer_email', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer email')
	->addColumn('rma_customer_company', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer email');
$installer->getConnection()->createTable($table5);

$table6 = $installer->getConnection()->newTable($rmaReutilTable)
->addColumn('rma_reutil_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'RMA Reutil ID')
    ->addColumn('rma_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'nullable'  => false,
        ), 'RMA ID')
	->addColumn('rma_items_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 20, array(
        'unsigned'  => true,
        ), 'RMA items ID')
    ->addColumn('rma_reutilstrategy', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA customer ID')
	->addColumn('rma_reutilamounttype', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA reutil amount type')
	->addColumn('rma_reutilamount', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA reutil amount')
	->addColumn('rma_vatamount', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA VAT amount')
	->addColumn('rma_reutilccy', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA reutil ccy')
	->addColumn('rma_reutilclass', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA reutil class')
	->addColumn('rma_reutillineno', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        ), 'RMA line no');
$installer->getConnection()->createTable($table6);

//@TODO ADD FOREIGN KEYS
//http://www.magentocommerce.com/wiki/5_-_modules_and_development/reference/adding_a_foreign_key_constraint_or_index_to_table

$data = array(
    array('rma_status_code' => 'rma_created', 'rma_status_label' => 'RMA Created', 'rma_status_alias' => 0),
    array('rma_status_code' => 'rma_complete', 'rma_status_label' => 'RMA Complete', 'rma_status_alias' => 0),
    array('rma_status_code' => 'rma_cancelled', 'rma_status_label' => 'RMA Cancelled', 'rma_status_alias' => 0)
);
$installer->getConnection()->insertArray($rmaStatusTable, array('rma_status_code', 'rma_status_label', 'rma_status_alias'), $data);
$installer->endSetup();
?>