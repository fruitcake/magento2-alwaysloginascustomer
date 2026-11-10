<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Test\Unit\Controller\Adminhtml\StoreOptions;

use Fruitcake\AlwaysLoginAsCustomer\Controller\Adminhtml\StoreOptions\Fetch;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FetchTest extends TestCase
{
    /** @var RequestInterface&MockObject */
    private $request;

    /** @var CustomerRepositoryInterface&MockObject */
    private $customerRepository;

    /** @var SystemStore&MockObject */
    private $systemStore;

    /** @var Share&MockObject */
    private $share;

    /** @var ConfigInterface&MockObject */
    private $config;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var array|null */
    private $responseData;

    /** @var Fetch */
    private $controller;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);

        $result = $this->createMock(Json::class);
        $result->method('setData')->willReturnCallback(function ($data) use ($result) {
            $this->responseData = $data;
            return $result;
        });
        $resultFactory = $this->createMock(JsonFactory::class);
        $resultFactory->method('create')->willReturn($result);

        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->systemStore = $this->createMock(SystemStore::class);
        $this->share = $this->createMock(Share::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new Fetch(
            $context,
            $resultFactory,
            $this->customerRepository,
            $this->systemStore,
            $this->share,
            $this->config,
            $this->logger
        );
    }

    public function testMissingCustomerIdReturnsError(): void
    {
        $this->request->method('getParam')->with('customer_id')->willReturn(null);
        $this->customerRepository->expects($this->never())->method('getById');

        $this->controller->execute();

        $this->assertSame(['error' => 'Customer ID is required.'], $this->responseData);
    }

    public function testNoOptionsWhenManualStoreChoiceIsDisabled(): void
    {
        $this->request->method('getParam')->willReturn('5');
        $this->config->method('isStoreManualChoiceEnabled')->willReturn(false);
        $this->customerRepository->expects($this->never())->method('getById');

        $this->controller->execute();

        $this->assertSame(['options' => []], $this->responseData);
    }

    public function testUnknownCustomerReturnsError(): void
    {
        $this->request->method('getParam')->willReturn('5');
        $this->config->method('isStoreManualChoiceEnabled')->willReturn(true);
        $this->customerRepository->method('getById')->with(5)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        $this->controller->execute();

        $this->assertSame(['error' => 'Customer with this ID no longer exists.'], $this->responseData);
    }

    public function testUnexpectedExceptionIsLoggedAndNotLeaked(): void
    {
        $this->request->method('getParam')->willReturn('5');
        $this->config->method('isStoreManualChoiceEnabled')->willReturn(true);
        $exception = new \RuntimeException('SQLSTATE[HY000] secret details');
        $this->customerRepository->method('getById')->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->controller->execute();

        $this->assertSame(['error' => 'Unable to fetch store options.'], $this->responseData);
    }

    public function testOptionsAreLimitedToCustomerWebsiteWhenAccountsArePerWebsite(): void
    {
        $this->givenCustomer('2', '3');
        $this->share->method('isGlobalScope')->willReturn(false);

        $this->controller->execute();

        $this->assertSame([
            'options' => [
                ['value' => 1, 'label' => 'Main Website / Main Store', 'disabled' => true, 'selected' => false],
                ['value' => 3, 'label' => 'B&B Website / "Quoted" <Store>', 'disabled' => false, 'selected' => true],
            ],
        ], $this->responseData);
    }

    public function testAllOptionsEnabledWhenAccountsAreGlobal(): void
    {
        $this->givenCustomer('2', '3');
        $this->share->method('isGlobalScope')->willReturn(true);

        $this->controller->execute();

        $this->assertFalse($this->responseData['options'][0]['disabled']);
        $this->assertFalse($this->responseData['options'][1]['disabled']);
    }

    /**
     * Two websites; website 2 also has a store group without store views, which must be skipped.
     *
     * Ids are given as strings, like Magento returns them from the database.
     *
     * @param string $websiteId
     * @param string $storeId
     */
    private function givenCustomer(string $websiteId, string $storeId): void
    {
        $this->request->method('getParam')->willReturn('5');
        $this->config->method('isStoreManualChoiceEnabled')->willReturn(true);

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getWebsiteId')->willReturn($websiteId);
        $customer->method('getStoreId')->willReturn($storeId);
        $this->customerRepository->method('getById')->with(5)->willReturn($customer);

        $this->systemStore->method('getWebsiteCollection')->willReturn([
            $this->website('1', 'Main Website'),
            $this->website('2', 'B&B Website'),
        ]);
        $this->systemStore->method('getGroupCollection')->willReturn([
            $this->group('1', 'Main Store', ['1' => '1', '2' => '2']),
            $this->group('2', 'Empty Store', []),
            $this->group('2', '"Quoted" <Store>', ['3' => '3', '4' => '4']),
        ]);
    }

    private function website(string $id, string $name): Website
    {
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn($id);
        $website->method('getName')->willReturn($name);
        return $website;
    }

    private function group(string $websiteId, string $name, array $storeIds): Group
    {
        $group = $this->createMock(Group::class);
        $group->method('getWebsiteId')->willReturn($websiteId);
        $group->method('getName')->willReturn($name);
        $group->method('getStoreIds')->willReturn($storeIds);
        return $group;
    }
}
