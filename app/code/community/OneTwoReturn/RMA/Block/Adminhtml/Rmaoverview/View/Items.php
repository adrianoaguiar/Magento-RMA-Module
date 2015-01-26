<?php
class OneTwoReturn_RMA_Block_Adminhtml_Rmaoverview_View_Items extends Mage_Adminhtml_Block_Sales_Items_Abstract
{
	
	public function getItem()
    {
        return $this->item;
    }
	
	public function getRma()
    {
        return Mage::registry('OneTwoReturn_RMA');
    }
	
	public function getRmaItem($item_id)
	{
		return Mage::getModel('rma/ritems')->getCollection()->addFieldToFilter('rma_items_rma_id',$this->getRma()->getRmaId())->addFieldToFilter('rma_items_order_item_id',$item_id)->getFirstItem()->getData();
	}
	
	public function getQtyReturning($_item) 
	{
		$rmaitem = $this->getRmaItem($_item->getItemId());
		return $rmaitem['rma_items_qty_returning'];
	}
	
	public function getQtyReturned($_item)
	{
		$rmaitem = $this->getRmaItem($_item->getItemId());
		return $rmaitem['rma_items_qty_returned'];
	}
	
	public function getQtyToStock($_item)
	{
		$rmaitem = $this->getRmaItem($_item->getItemId());
		return $rmaitem['rma_items_qty_tostock'];
	}
	
	public function getQtyToCredit($_item)
	{
		$rmaitem = $this->getRmaItem($_item->getItemId());
		return $rmaitem['rma_items_qty_tocredit'];
	}
    
    public function getExchangePref($_item)
    {
        $rmaitem = $this->getRmaItem($_item->getItemId());
        return $rmaitem['exchange_pref'];
    }
    /**
     * Retrieve real html id for field
     *
     * @param string $name
     * @return string
     */
    public function getFieldId($id)
    {
        return $this->getFieldIdPrefix() . $id;
    }

    /**
     * Retrieve field html id prefix
     *
     * @return string
     */
    public function getFieldIdPrefix()
    {
        return 'order_item_' . $this->getItem()->getId() . '_';
    }

    /**
     * Indicate that block can display container
     *
     * @return boolean
     */
    public function canDisplayContainer()
    {
        return $this->getRequest()->getParam('reload') != 1;
    }

    /**
     * Giftmessage object
     *
     * @deprecated after 1.4.2.0
     * @var Mage_GiftMessage_Model_Message
     */
    protected $_giftMessage = array();

    /**
     * Retrive default value for giftmessage sender
     *
     * @deprecated after 1.4.2.0
     * @return string
     */
    public function getDefaultSender()
    {
        if(!$this->getItem()) {
            return '';
        }

        if($this->getItem()->getOrder()) {
            return $this->getItem()->getOrder()->getBillingAddress()->getName();
        }

        return $this->getItem()->getBillingAddress()->getName();
    }

    /**
     * Retrive default value for giftmessage recipient
     *
     * @deprecated after 1.4.2.0
     * @return string
     */
    public function getDefaultRecipient()
    {
        if(!$this->getItem()) {
            return '';
        }

        if($this->getItem()->getOrder()) {
            if ($this->getItem()->getOrder()->getShippingAddress()) {
                return $this->getItem()->getOrder()->getShippingAddress()->getName();
            } else if ($this->getItem()->getOrder()->getBillingAddress()) {
                return $this->getItem()->getOrder()->getBillingAddress()->getName();
            }
        }

        if ($this->getItem()->getShippingAddress()) {
            return $this->getItem()->getShippingAddress()->getName();
        } else if ($this->getItem()->getBillingAddress()) {
            return $this->getItem()->getBillingAddress()->getName();
        }

        return '';
    }

    /**
     * Retrive real name for field
     *
     * @deprecated after 1.4.2.0
     * @param string $name
     * @return string
     */
    public function getFieldName($name)
    {
        return 'giftmessage[' . $this->getItem()->getId() . '][' . $name . ']';
    }

    /**
     * Initialize gift message for entity
     *
     * @deprecated after 1.4.2.0
     * @return Mage_Adminhtml_Block_Sales_Order_Edit_Items_Grid_Renderer_Name_Giftmessage
     */
    protected function _initMessage()
    {
        $this->_giftMessage[$this->getItem()->getGiftMessageId()] =
            $this->helper('giftmessage/message')->getGiftMessage($this->getItem()->getGiftMessageId());

        // init default values for giftmessage form
        if(!$this->getMessage()->getSender()) {
            $this->getMessage()->setSender($this->getDefaultSender());
        }
        if(!$this->getMessage()->getRecipient()) {
            $this->getMessage()->setRecipient($this->getDefaultRecipient());
        }

        return $this;
    }

    /**
     * Retrive gift message for entity
     *
     * @deprecated after 1.4.2.0
     * @return Mage_GiftMessage_Model_Message
     */
    public function getMessage()
    {
        if(!isset($this->_giftMessage[$this->getItem()->getGiftMessageId()])) {
            $this->_initMessage();
        }

        return $this->_giftMessage[$this->getItem()->getGiftMessageId()];
    }

    /**
     * Retrieve save url
     *
     * @deprecated after 1.4.2.0
     * @return array
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/sales_order_view_giftmessage/save', array(
            'entity'    => $this->getItem()->getId(),
            'type'      => 'order_item',
            'reload'    => true
        ));
    }

    /**
     * Retrive block html id
     *
     * @deprecated after 1.4.2.0
     * @return string
     */
    public function getHtmlId()
    {
        return substr($this->getFieldIdPrefix(), 0, -1);
    }

    /**
     * Indicates that block can display giftmessages form
     *
     * @deprecated after 1.4.2.0
     * @return boolean
     */
    public function canDisplayGiftmessage()
    {
        return $this->helper('giftmessage/message')->getIsMessagesAvailable(
            'order_item', $this->getItem(), $this->getItem()->getOrder()->getStoreId()
        );
    }

    /**
     * Display susbtotal price including tax
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @return string
     */
    public function displaySubtotalInclTax($item)
    {
        return $this->displayPrices(
            $this->helper('checkout')->getBaseSubtotalInclTax($item),
            $this->helper('checkout')->getSubtotalInclTax($item)
        );
    }

    /**
     * Display item price including tax
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @return string
     */
    public function displayPriceInclTax(Varien_Object $item)
    {
        return $this->displayPrices(
            $this->helper('checkout')->getBasePriceInclTax($item),
            $this->helper('checkout')->getPriceInclTax($item)
        );
    }
}