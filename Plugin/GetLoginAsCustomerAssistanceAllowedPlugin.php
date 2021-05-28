<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Plugin;

use Magento\LoginAsCustomerAssistance\Model\ResourceModel\GetLoginAsCustomerAssistanceAllowed;

class GetLoginAsCustomerAssistanceAllowedPlugin
{
    public function afterExecute(
        GetLoginAsCustomerAssistanceAllowed $subject,
    	bool $result
    ) {
    	return true;
    }
}
