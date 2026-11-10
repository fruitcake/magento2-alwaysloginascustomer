<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Plugin;

use Magento\LoginAsCustomerAssistance\Model\ResourceModel\GetLoginAsCustomerAssistanceAllowed;

/**
 * Treat every customer as having allowed remote shopping assistance.
 */
class GetLoginAsCustomerAssistanceAllowedPlugin
{
    /**
     * Always allow Login as Customer, regardless of the customer's opt-in.
     *
     * @param GetLoginAsCustomerAssistanceAllowed $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(GetLoginAsCustomerAssistanceAllowed $subject, bool $result): bool
    {
        return true;
    }
}
