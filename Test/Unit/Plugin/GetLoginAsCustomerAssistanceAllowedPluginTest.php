<?php
namespace Fruitcake\AlwaysLoginAsCustomer\Test\Unit\Plugin;

use Fruitcake\AlwaysLoginAsCustomer\Plugin\GetLoginAsCustomerAssistanceAllowedPlugin;
use Magento\LoginAsCustomerAssistance\Model\ResourceModel\GetLoginAsCustomerAssistanceAllowed;
use PHPUnit\Framework\TestCase;

class GetLoginAsCustomerAssistanceAllowedPluginTest extends TestCase
{
    /**
     * @return array
     */
    public static function resultProvider(): array
    {
        return [
            'customer opted out' => [false],
            'customer opted in' => [true],
        ];
    }

    /**
     * @dataProvider resultProvider
     * @param bool $original
     */
    public function testAlwaysAllowsAssistance(bool $original): void
    {
        $plugin = new GetLoginAsCustomerAssistanceAllowedPlugin();
        $subject = $this->createMock(GetLoginAsCustomerAssistanceAllowed::class);

        $this->assertTrue($plugin->afterExecute($subject, $original));
    }
}
