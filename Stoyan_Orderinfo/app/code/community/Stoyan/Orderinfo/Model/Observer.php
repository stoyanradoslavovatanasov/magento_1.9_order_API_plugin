<?php
/* 
PlugIn created by Stoyan Atanasov.
The observer.php file. Here we collect all the order information in JSON, 
after the status of the order has been change and we are sending it to the API.
*/
class Stoyan_Orderinfo_Model_Observer
{
	
    public function ImplementOrderStatus(Varien_Event_Observer $observer) {
		
		
        $order = $observer->getEvent()->getOrder();
        $stateProcessing = Mage_Sales_Model_Order::STATE_PROCESSING;
		$billingaddress = $order->getBillingAddress();
		
		$switch_getpath = Mage::getModel('core/config_data')->load('tab1/general/active', 'path');
		$switch_getpath_value = $switch_getpath['value'];
	
		if ($switch_getpath_value == 1){

			$url_getpath = Mage::getModel('core/config_data')->load('tab1/general/text_field', 'path');
			$url = $url_getpath['value'];
			
			$orderid = $order->getData('entity_id');
			
			$status_new = $order->getState();
					
			$resource = Mage::getSingleton('core/resource');
		
			$readConnection = $resource->getConnection('core_read');
		
			$table = $resource->getTableName('sales/order_status_history');
		
			$query_getstatus = 'SELECT status FROM  sales_flat_order_status_history WHERE parent_id = ' . (int)$orderid . ' ORDER BY entity_id DESC LIMIT 2';
		
			$status_query = $readConnection->fetchAll($query_getstatus);
			$status_old = end($status_query);
			$status_old2 = $status_old['status'];
		
			
			$query_pmethod = 'SELECT method FROM  sales_flat_order_payment WHERE parent_id = ' . (int)$orderid ;
			$pmethod_new = $readConnection->fetchOne($query_pmethod);			
			
			if ($status_old2 != $status_new ){
				
				try { 
					$orderDATA = array(
						'order_id' => $order->getData('entity_id'),
						'order_data' => array (
							'order_status' =>$order->getState(),
							'payment_method' =>$pmethod_new,
							'number_of_items' =>$order->getData('total_item_count'),
							'sub_total' =>$order->getData('base_subtotal'),
							'discount' =>$order->getData('base_discount_amount'),
							'grand_total' =>$order->getGrandTotal()
						),
						'customer_data' => array (
							'customer_id' => $order->getData('customer_id'),
							'firstname'=> $order->getData('customer_firstname'),
							'lastname'=> $order->getData('customer_lastname'),
							'address' => $billingaddress->getData('street'),
							'city' =>  $billingaddress->getData('city'),
							'country'=>  $billingaddress->getData('region'),
							'email'=> $billingaddress->getData('email')
						)
					);
					
					$data_json = json_encode($orderDATA);
					
					//$url ='http://jsonplaceholder.typicode.com/posts/1'; // Perfect for testing the request 
					
					$ch = curl_init();   
					curl_setopt($ch, CURLOPT_URL, $url);		
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");                                                                     
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);                                                                  
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                       
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));																								
					$result = curl_exec($ch);
						
					//Mage::log('Order has been sent:  ' . $result, null, 'Stoyan_orderinfo.log');
					
					} 
					catch (Exception $e) {
					Mage::logException($e);}
					
				}
		}
			
	}
 }   
 ?>