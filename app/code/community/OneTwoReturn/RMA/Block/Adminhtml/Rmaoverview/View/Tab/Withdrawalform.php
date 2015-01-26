<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Tab_Withdrawalform extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init class
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('onetworeturn_rma/rmaoverview/view/tab/withdrawalform.phtml');
        
    }  
    
    public function getOrder()
    {
        return Mage::registry('current_order');
    } 
    
    public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }
    
    public function getAddress()
    {
         return Mage::getModel('rma/raddress')->getCollection()->addFieldToFilter('rma_id', $this->getRma()->getRmaId())->getFirstItem();
    }
    
    public function getConditions()
    {
         return Mage::getModel('rma/rconditions')->getCollection()->addFieldToFilter('rma_id', $this->getRma()->getRmaId());
    }
    
    public function getRmaStatusLabel()
    {
        return Mage::getModel('rma/rstatus')->load($this->getRma()->getRmaStatusCode())->getRmaStatusLabel();
    }   
    
    public function getHeaderText()
    {
        return $this->__('RMA #'.$this->getRma()->getRmaReference().' | '.$this->getRma()->getRmaCreatedate()); 
    }  

    public function getCustomerGroupName($gid)
    {
        if ($this->getOrder()) {
            return Mage::getModel('customer/group')->load((int) $gid)->getCode();
        }
        return null;
    }
    
    public function getRmaContext()
    {
        return Mage::helper('rma')->returnTypes[$this->getRma()->getRmaContext()];
    }
    
    public function getOrderStoreName($storeId)
    {
        if ($this->getOrder()) {
            
            if (is_null($storeId)) {
                $deleted = Mage::helper('adminhtml')->__(' [deleted]');
                return nl2br($this->getOrder()->getStoreName()) . $deleted;
            }
            $store = Mage::app()->getStore($storeId);
            $name = array(
                $store->getWebsite()->getName(),
                $store->getGroup()->getName(),
                $store->getName()
            );
            return implode('<br/>', $name);
        }
        return null;
    }
    
    public function getCustomerViewUrl($cid)
    {
        if (!$cid || $cid==0) {
            return false;
        }
        return $this->getUrl('adminhtml/customer/edit', array('id' => $cid));
    }
    
    /**
     * Return array of additional account data
     * Value is option style array
     *
     * @return array
     */
    public function getCustomerAccountData()
    {
        $accountData = array();

        /* @var $config Mage_Eav_Model_Config */
        $config     = Mage::getSingleton('eav/config');
        $entityType = 'customer';
        $customer   = Mage::getModel('customer/customer');
        foreach ($config->getEntityAttributeCodes($entityType) as $attributeCode) {
            /* @var $attribute Mage_Customer_Model_Attribute */
            $attribute = $config->getAttribute($entityType, $attributeCode);
            if (!$attribute->getIsVisible() || $attribute->getIsSystem()) {
                continue;
            }
            $orderKey   = sprintf('customer_%s', $attribute->getAttributeCode());
            $orderValue = $this->getOrder()->getData($orderKey);
            if ($orderValue != '') {
                $customer->setData($attribute->getAttributeCode(), $orderValue);
                $dataModel  = Mage_Customer_Model_Attribute_Data::factory($attribute, $customer);
                $value      = $dataModel->outputValue(Mage_Customer_Model_Attribute_Data::OUTPUT_FORMAT_HTML);
                $sortOrder  = $attribute->getSortOrder() + $attribute->getIsUserDefined() ? 200 : 0;
                if(method_exists ($this,'_prepareAccountDataSortOrder')) $sortOrder  = $this->_prepareAccountDataSortOrder($accountData, $sortOrder);
                $accountData[$sortOrder] = array(
                    'label' => $attribute->getFrontendLabel(),
                    'value' => $this->escapeHtml($value, array('br'))
                );
            }
        }

        ksort($accountData, SORT_NUMERIC);

        return $accountData;
    }
    
    public function shouldDisplayCustomerIp($storeid)
    {
        return !Mage::getStoreConfigFlag('sales/general/hide_customer_ip', $storeid);
    }

}