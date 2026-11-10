<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Controller\Adminhtml\StoreOptions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Store\Model\Website;
use Psr\Log\LoggerInterface;

/**
 * Returns the store options shown in the "Login as Customer" popup on the customer grid.
 */
class Fetch extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Magento_LoginAsCustomer::login';

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
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SystemStore $systemStore
     * @param Share $share
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerRepositoryInterface $customerRepository,
        SystemStore $systemStore,
        Share $share,
        ConfigInterface $config,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerRepository = $customerRepository;
        $this->systemStore = $systemStore;
        $this->share = $share;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Fetch store options for a customer
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $customerId = (int)$this->getRequest()->getParam('customer_id');

        if ($customerId <= 0) {
            return $result->setData(['error' => (string)__('Customer ID is required.')]);
        }

        if (!$this->config->isStoreManualChoiceEnabled()) {
            return $result->setData(['options' => []]);
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            return $result->setData(['error' => (string)__('Customer with this ID no longer exists.')]);
        } catch (LocalizedException $e) {
            return $result->setData(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $result->setData(['error' => (string)__('Unable to fetch store options.')]);
        }

        return $result->setData(['options' => $this->generateStoreOptions($customer)]);
    }

    /**
     * Generate store options for customer.
     *
     * Labels are returned as plain text; escaping is done by the client when rendering.
     *
     * @param CustomerInterface $customer
     * @return array
     */
    private function generateStoreOptions(CustomerInterface $customer): array
    {
        $options = [];
        $isGlobalScope = $this->share->isGlobalScope();
        $customerWebsiteId = (int)$customer->getWebsiteId();
        $customerStoreId = (int)$customer->getStoreId();
        $groupCollection = $this->systemStore->getGroupCollection();

        /** @var Website $website */
        foreach ($this->systemStore->getWebsiteCollection() as $website) {
            $websiteId = (int)$website->getId();

            /** @var Group $group */
            foreach ($groupCollection as $group) {
                if ((int)$group->getWebsiteId() !== $websiteId) {
                    continue;
                }

                $storeViewIds = array_map('intval', array_values($group->getStoreIds()));
                if (empty($storeViewIds)) {
                    continue;
                }

                $options[] = [
                    'value' => $storeViewIds[0],
                    'label' => $website->getName() . ' / ' . $group->getName(),
                    'disabled' => !$isGlobalScope && $customerWebsiteId !== $websiteId,
                    'selected' => in_array($customerStoreId, $storeViewIds, true),
                ];
            }
        }

        return $options;
    }
}
