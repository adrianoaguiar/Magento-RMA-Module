<?php

class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Retrieve available order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->getData('order');
        }
        if (Mage::registry('current_order')) {
            return Mage::registry('current_order');
        }
        if (Mage::registry('order')) {
            return Mage::registry('order');
        }
        Mage::throwException(Mage::helper('sales')->__('Cannot get the order instance.'));
    }

    public function __construct()
    {
        parent::__construct();
        $this->setId('rma_view_tabs');
        $this->setDestElementId('rma_view');
        $this->setTitle(Mage::helper('sales')->__('RMA view'));
    }
	
	protected function _beforeToHtml()
	{
		
		 $this->addTab('rma_info_tab', array(
		      'label'     => Mage::helper('sales')->__('RMA Information'),
		      'title'     => Mage::helper('sales')->__('RMA Information'),
		      //'class' =>   'ajax',
		      'content'   => $this->getChildHtml('rma_info'),//createBlock('rma/adminhtml_rmaoverview_view_info')->toHtml(),
		      //'url'   =>   $this->getUrl('*/*/info',array('_current'=>true)),
	      ));

	      $this->addTab('rma_label_tab', array(
		      'label'     => Mage::helper('sales')->__('RMA Label'),
		      'title'     => Mage::helper('sales')->__('RMA Label'),
		      'class' =>   'ajax',
		      'url'   =>   $this->getUrl('*/*/label',array('_current'=>true)),
		      //'content'   => $this->getLayout()->createBlock('rma/adminhtml_rmaoverview_view_tab_label')->toHtml(),
	      ));
	      
	      return parent::_beforeToHtml();
	}

}