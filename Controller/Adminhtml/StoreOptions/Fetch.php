<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Controller\Adminhtml\StoreOptions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Escaper;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Store\Model\Website;

class Fetch extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magento_LoginAsCustomer::login';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SystemStore
     */
    private $systemStore;

    /**
     * @var Share
     */
    private $share;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SystemStore $systemStore
     * @param Share $share
     * @param Escaper $escaper
     * @param ConfigInterface $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerRepositoryInterface $customerRepository,
        SystemStore $systemStore,
        Share $share,
        Escaper $escaper,
        ConfigInterface $config
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerRepository = $customerRepository;
        $this->systemStore = $systemStore;
        $this->share = $share;
        $this->escaper = $escaper;
        $this->config = $config;
    }

    /**
     * Fetch store options for a customer
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $customerId = (int)$this->getRequest()->getParam('customer_id');
            
            if (!$customerId) {
                return $result->setData(['error' => __('Customer ID is required.')]);
            }

            // Check if store manual choice is enabled
            if (!$this->config->isStoreManualChoiceEnabled()) {
                return $result->setData(['options' => []]);
            }

            $customer = $this->customerRepository->getById($customerId);
            $options = $this->generateStoreOptions($customer);
            
            return $result->setData(['options' => $options]);
            
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate store options for customer
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return array
     */
    private function generateStoreOptions($customer)
    {
        $options = [];
        $websiteCollection = $this->systemStore->getWebsiteCollection();
        $isGlobalScope = $this->share->isGlobalScope();
        $customerWebsiteId = $customer->getWebsiteId();
        $customerStoreId = $customer->getStoreId();

        /** @var Website $website */
        foreach ($websiteCollection as $website) {
            $websiteId = $website->getId();
            $websiteName = $this->sanitizeName($website->getName());
            
            $groupCollection = $this->systemStore->getGroupCollection();
            
            /** @var Group $group */
            foreach ($groupCollection as $group) {
                if ($group->getWebsiteId() == $websiteId) {
                    $storeViewIds = $group->getStoreIds();
                    if (!empty($storeViewIds)) {
                        $groupName = $this->sanitizeName($group->getName());
                        $label = $websiteName . ' / ' . $groupName;
                        
                        $options[] = [
                            'value' => array_values($storeViewIds)[0],
                            'label' => $label,
                            'disabled' => !$isGlobalScope && $customerWebsiteId !== $websiteId,
                            'selected' => in_array($customerStoreId, $storeViewIds)
                        ];
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Sanitize name
     *
     * @param string $name
     * @return string
     */
    private function sanitizeName(string $name): string
    {
        $matches = [];
        preg_match('/\$[:]*{(.)*}/', $name, $matches);
        if (count($matches) > 0) {
            $name = $this->escaper->escapeHtml($this->escaper->escapeJs($name));
        } else {
            $name = $this->escaper->escapeHtml($name);
        }

        return $name;
    }
}
