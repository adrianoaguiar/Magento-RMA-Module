<?php
class OneTwoReturn_RMA_Block_Adminhtml_Customer_Edit_Tab_Rma
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    
    protected function _construct()
    {
     	parent::_construct();
		$this->setId('rmaGrid');
		$this->setUseAjax(true);
        $this->setDefaultSort('rma_created');
        $this->setDefaultDir('ASC');
		$this->setFilterVisibility(false);
		$this->setPagerVisibility(false);
        $this->setSaveParametersInSession(true);
    }

	public function getCustomer()
    {
        return Mage::registry('current_customer');
    }
	
	protected function _prepareCollection()
    {
    	
      $collection = Mage::getModel('rma/rma')->getCollection()->addFieldToFilter('rma_customer_id', $this->getCustomer()->getId());
      $this->setCollection($collection);

      return parent::_prepareCollection();
    }

	protected function _prepareColumns()
    {
        // Add the columns that should appear in the grid
        $this->addColumn('rma_id',
            array(
                'header'=> $this->__('ID'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'rma_id'
            )
        );
         
        $this->addColumn('rma_reference',
            array(
                'header'=> $this->__('RMA #'),
                'index' => 'rma_reference',
                'width' => '130px',
                'renderer' =>  'OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Rmareference'
            )
        );
		$this->addColumn('rma_customer',
            array(
                'header'=> $this->__('Customer'),
                'index' => 'rma_order_entity_id',
                'renderer' =>  'OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Customer'
            )
        );
		
		$this->addColumn('rma_status_code',
            array(
                'header'=> $this->__('Status'),
                'type'  => 'options',
                'index' => 'rma_status_code',
                'width' => 120,
                'renderer' =>  new OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Status(),
                'options'=> OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Status::getOptions()
            )
        );
		
		$this->addColumn('rma_order_increment_id',
            array(
                'header'=> $this->__('Order #'),
                'type' => 'text',
                'index' => 'rma_order_increment_id',
                'width' => 120,
            	'type' => 'text',
            	'renderer' =>  'OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Orderno'
            )
        );
		
		$this->addColumn('rma_context',
            array(
                'header'=> $this->__('RMA Type'),
                'type'      => 'options',
                'index' => 'rma_context',
                'width' => '140px',
                'renderer' =>  new OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Context(),
                'options'=> OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Context::getOptions()
            )
        );
		
		$this->addColumn('rma_createdate',
            array(
                'header'=> $this->__('RMA Created at'),
                'type'      => 'date',
                'width' => 70,
                'index' => 'rma_createdate'
            )
        );
		
		$this->addColumn('rma_updatedate',
            array(
                'header'=> $this->__('RMA Updated at'),
                'type'      => 'date',
                'width' => 70,
                'index' => 'rma_updatedate'
            )
        );

         
        return parent::_prepareColumns();
    }

	public function getRowUrl($row)
    {
        return $this->getUrl(
            '12return/adminhtml_rmaoverview/view',
            array(
                'rma_id'=> $row->getRmaId(),
                'order_id'=> $row->getRmaOrderEntityId()
             ));
    }

    public function getGridUrl()
    {
        return $this->getUrl('12return/adminhtml_rmaoverview');
    }

    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('RMAs');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('RMAs');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}