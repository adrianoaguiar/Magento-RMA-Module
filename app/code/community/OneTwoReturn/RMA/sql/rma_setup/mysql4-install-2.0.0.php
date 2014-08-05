<?php


$installer = $this;
$installer->startSetup();

Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
//$setup = new Mage_Sales_Model_Mysql4_Setup;
$setup = new Mage_Sales_Model_Mysql4_Setup('core_setup');
$attribute  = array(
			        'type'          => 'text',
			        'backend_type'  => 'text',
			        'frontend_input' => 'text',
			        'is_user_defined' => true,
			        'label'         => 'selectiveRma',
			        'visible'       => true,
			        'required'      => false,
			        'user_defined'  => false,
			        'searchable'    => false,
			        'filterable'    => false,
			        'comparable'    => false,
			        'default'       => ''
			);
$setup->addAttribute('order', 'selective_rma', $attribute);
$attribute  = array(
			        'type'          => 'text',
			        'backend_type'  => 'text',
			        'frontend_input' => 'text',
			        'is_user_defined' => true,
			        'label'         => 'selectiveRmaWarranty',
			        'visible'       => true,
			        'required'      => false,
			        'user_defined'  => false,
			        'searchable'    => false,
			        'filterable'    => false,
			        'comparable'    => false,
			        'default'       => ''
);			   
$setup->addAttribute('order', 'selective_rma_warranty', $attribute);
 

/*
 * Adding RMA order status & link to order state
 */
 
$orderTable 		= $installer->getTable('sales/order');
$statusTable        = $installer->getTable('sales/order_status');
$statusStateTable   = $installer->getTable('sales/order_status_state');
$statusLabelTable   = $installer->getTable('sales/order_status_label');

$data = array(
    array('status' => 'rma_created', 'label' => 'RMA Created'),
    array('status' => 'rma_complete', 'label' => 'RMA Complete'),
);
$installer->getConnection()->insertArray($statusTable, array('status', 'label'), $data);

$data = array(
    array('status' => 'rma_created', 'state' => 'complete', 'is_default' => 0),
    array('status' => 'rma_complete', 'state' => 'complete', 'is_default' => 0),
    
);

$installer->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $data);
$installer->getConnection()->addColumn($installer->getTable('sales/order_item'),'qty_returned', 'decimal');
$installer->getConnection()->addColumn($installer->getTable('sales/order_item'),'qty_returning', 'decimal');

$installer->endSetup();
/* EOF */