<?php

class OneTwoReturn_RMA_Model_Observers_Creditnota
{   
    public function changeRmaStatus(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        
        $rma_changed = false;
        foreach ($creditMemo->getAllItems() as $credititem) 
        {
            $orderItem = $credititem->getOrderItem();
            $orderItem->setQtyReturning(0);
            
            $orderItem->save();
        } 
        
        if(isset($order) && !is_null($order) && $rma_changed)
        {
            $order->setData('state', "complete");
            $order->setStatus("rma_complete");
            $history = $order->addStatusHistoryComment('RMA Completed.', false);
            $history->setIsCustomerNotified(false);
            $order->save();
        }
    } 
}
