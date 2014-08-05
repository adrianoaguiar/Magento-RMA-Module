<?php
class OneTwoReturn_Rma_Block_Adminhtml_Rmaoverview_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        // Set some defaults for our grid
        $this->setId('rmaGrid');
		$this->setUseAjax(true);
        $this->setDefaultSort('rma_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
		
    }
	
     
    protected function _prepareCollection()
    {
    	
      $collection = Mage::getModel('rma/rma')->getCollection();
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
                'width' => '130px'
            )
        );
		if ($this->_isExport) 
		{
			$this->addColumn('rma_customer',
	            array(
	                'header'=> $this->__('Customer'),
	                'index' => 'rma_order_entity_id',
	                'renderer' =>  'OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Export_Customer'
	            )
	        );
		} else {
			$this->addColumn('rma_customer',
            array(
                'header'=> $this->__('Customer'),
                'index' => 'rma_order_entity_id',
                'renderer' =>  'OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Customer'
            )
        );
		}
		
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
		if ($this->_isExport) 
		{
			$this->addColumn('rma_order_increment_id',
	            array(
	                'header'=> $this->__('Order #'),
	                'type' => 'text',
	                'index' => 'rma_order_increment_id',
	                'width' => 120,
	            	'type' => 'text',
	            	'renderer' =>  'OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_Renderer_Export_Orderno'
	            )
	        );
	    } else {
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
	    }
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
		
		//$this->addRssList('rss/rmaoverview/new', Mage::helper('sales')->__('New Rma RSS'));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));
         
        return parent::_prepareColumns();
    }

	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('rma_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);
		

        return $this;
    }
     
    public function getRowUrl($row)
    {
        // This is where our row data will link to
        $data=$row->getData();
        return $this->getUrl('*/*/view', array('rma_id' => $data['rma_id'],'order_id'=>$data['rma_order_entity_id']));
    }
	
	public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
	
}