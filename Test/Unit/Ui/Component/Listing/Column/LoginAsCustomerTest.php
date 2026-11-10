<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Test\Unit\Ui\Component\Listing\Column;

use Fruitcake\AlwaysLoginAsCustomer\Ui\Component\Listing\Column\LoginAsCustomer;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginAsCustomerTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private $config;

    /** @var AuthorizationInterface&MockObject */
    private $authorization;

    /** @var LoginAsCustomer */
    private $column;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(function ($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES);
        });
        $escaper->method('escapeHtmlAttr')->willReturnCallback(function ($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES);
        });

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(function ($route, $params = null) {
            $url = 'https://admin.test/' . $route;
            foreach ((array)$params as $key => $value) {
                $url .= '/' . $key . '/' . $value;
            }
            return $url . '/';
        });

        $this->column = new LoginAsCustomer(
            $this->createMock(ContextInterface::class),
            $this->createMock(UiComponentFactory::class),
            $this->config,
            $this->authorization,
            $escaper,
            $urlBuilder,
            [],
            ['name' => 'login_as_customer']
        );
    }

    public function testRendersLoginButtonPerCustomer(): void
    {
        $this->givenEnabledAndAllowed();

        $result = $this->column->prepareDataSource([
            'data' => ['items' => [['entity_id' => '42'], ['entity_id' => '7']]],
        ]);

        $html = $result['data']['items'][0]['login_as_customer'];
        $this->assertStringStartsWith('<button type="button" onclick="', $html);
        $this->assertStringContainsString('Login as customer</button>', $html);

        $onclick = html_entity_decode($this->extractOnclick($html), ENT_QUOTES);
        $this->assertSame(
            'require(["Fruitcake_AlwaysLoginAsCustomer/js/grid-login-popup"], function (popup) { popup('
            . '"https:\/\/admin.test\/loginascustomer\/login\/login\/customer_id\/42\/", 42, '
            . '"https:\/\/admin.test\/fruitcake_loginascustomer\/storeoptions\/fetch\/"); }); return false;',
            $onclick
        );
        $this->assertStringContainsString(
            'customer_id\/7\/", 7,',
            html_entity_decode($this->extractOnclick($result['data']['items'][1]['login_as_customer']), ENT_QUOTES)
        );
    }

    public function testDoesNotOutputFormKeyInputs(): void
    {
        $this->givenEnabledAndAllowed();

        $result = $this->column->prepareDataSource(['data' => ['items' => [['entity_id' => '1']]]]);

        $this->assertStringNotContainsString('form_key', $result['data']['items'][0]['login_as_customer']);
    }

    public function testSkipsRowsWithoutEntityId(): void
    {
        $this->givenEnabledAndAllowed();

        $result = $this->column->prepareDataSource(['data' => ['items' => [['email' => 'a@example.com']]]]);

        $this->assertSame([['email' => 'a@example.com']], $result['data']['items']);
    }

    public function testLeavesDataUntouchedWhenFeatureIsDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->authorization->method('isAllowed')->willReturn(true);
        $dataSource = ['data' => ['items' => [['entity_id' => '1']]]];

        $this->assertSame($dataSource, $this->column->prepareDataSource($dataSource));
    }

    public function testLeavesDataUntouchedWhenAdminIsNotAllowed(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->authorization->method('isAllowed')->with('Magento_LoginAsCustomer::login')->willReturn(false);
        $dataSource = ['data' => ['items' => [['entity_id' => '1']]]];

        $this->assertSame($dataSource, $this->column->prepareDataSource($dataSource));
    }

    public function testHandlesDataSourceWithoutItems(): void
    {
        $this->givenEnabledAndAllowed();

        $this->assertSame(['data' => ['totalRecords' => 0]], $this->column->prepareDataSource([
            'data' => ['totalRecords' => 0],
        ]));
    }

    private function givenEnabledAndAllowed(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->authorization->method('isAllowed')->willReturn(true);
    }

    private function extractOnclick(string $html): string
    {
        $this->assertSame(1, preg_match('/onclick="([^"]*)"/', $html, $matches));
        return $matches[1];
    }
}
