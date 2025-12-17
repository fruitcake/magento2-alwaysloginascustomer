<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Ui\Component\Listing\Column;


use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\LoginAsCustomerAdminUi\Ui\Customer\Component\Button\DataProvider;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class LoginAsCustomer extends Column
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * @var \Magento\Framework\Data\Form\FormKey 
     */
    private $formKey;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ConfigInterface $config,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\Data\Form\FormKey $formKey,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        ?DataProvider $dataProvider = null,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->authorization = $authorization;
        $this->config = $config;
        $this->dataProvider = $dataProvider ?? ObjectManager::getInstance()->get(DataProvider::class);
        $this->formKey = $formKey;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        $isAllowed = $this->authorization->isAllowed('Magento_LoginAsCustomer::login');
        $isEnabled = $this->config->isEnabled();

        if (!$isEnabled || !$isAllowed) {
            return $dataSource;
        }

        // FormKey needs to be available for the popup
        $formKey = $this->formKey->getFormKey();

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $customerId = $item['entity_id'];
                $loginUrl = $this->getLoginUrl($customerId);
                $fetchOptionsUrl = $this->urlBuilder->getUrl('fruitcake_loginascustomer/storeoptions/fetch');
                
                $onclick = sprintf(
                    'require(["Fruitcake_AlwaysLoginAsCustomer/js/grid-login-popup"], function(popup) { popup(%s, %d, %s); }); return false;',
                    json_encode($loginUrl),
                    $customerId,
                    json_encode($fetchOptionsUrl)
                );
                
                $item[$this->getName()] = '<input name="form_key" type="hidden" value="'.$formKey.'"/>
                <button onclick="' .$this->escaper->escapeHtmlAttr($onclick) .'">'.$this->escaper->escapeHtml(__('Login as customer')) .'</button>';
            }
        }

        return $dataSource;
    }

    /**
     * Get Login as Customer login url.
     *
     * @param int $customerId
     * @return string
     */
    private function getLoginUrl(int $customerId): string
    {
        return $this->urlBuilder->getUrl('loginascustomer/login/login', ['customer_id' => $customerId]);
    }
}
