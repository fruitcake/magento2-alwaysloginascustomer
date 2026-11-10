<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Ui\Component\Listing\Column;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Adds a "Login as customer" button to each row of the admin customer grid.
 */
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
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ConfigInterface $config
     * @param AuthorizationInterface $authorization
     * @param Escaper $escaper
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ConfigInterface $config,
        AuthorizationInterface $authorization,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->authorization = $authorization;
        $this->config = $config;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Render the login button for every customer row.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])
            || !$this->config->isEnabled()
            || !$this->authorization->isAllowed('Magento_LoginAsCustomer::login')
        ) {
            return $dataSource;
        }

        $fetchOptionsUrl = $this->urlBuilder->getUrl('fruitcake_loginascustomer/storeoptions/fetch');
        $label = $this->escaper->escapeHtml(__('Login as customer'));

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['entity_id'])) {
                continue;
            }

            $customerId = (int)$item['entity_id'];
            $onclick = sprintf(
                'require(["Fruitcake_AlwaysLoginAsCustomer/js/grid-login-popup"], function (popup) { '
                . 'popup(%s, %d, %s); }); return false;',
                json_encode($this->getLoginUrl($customerId)),
                $customerId,
                json_encode($fetchOptionsUrl)
            );

            $item[$this->getName()] = '<button type="button" onclick="'
                . $this->escaper->escapeHtmlAttr($onclick) . '">' . $label . '</button>';
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
