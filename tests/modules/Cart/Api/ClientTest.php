<?php
namespace Box\Tests\Mod\Cart\Api;


class ClientTest extends \BBTestCase {
    /**
     * @var \Box\Mod\Cart\Api\Client
     */
    protected $clientApi = null;

    public function setup(): void
    {
        $this->clientApi = new \Box\Mod\Cart\Api\Client();
    }

    public function testCheckout()
    {
        $cart = new \Model_Cart();
        $cart->loadBean(new \DummyBean());

       $serviceMock = $this->getMockBuilder('\Box\Mod\Cart\Service')
            ->setMethods(array('getSessionCart', 'checkoutCart'))
           ->getMock();
       $serviceMock->expects($this->atLeastOnce())->method('getSessionCart')
            ->will($this->returnValue($cart));

        $checkOutCartResult =  array (
            'gateway_id'   => 1,
            'invoice_hash' => null,
            'order_id'     => 1,
            'orders'       => 1,
        );
        $serviceMock->expects($this->atLeastOnce())
            ->method('checkoutCart')
            ->will($this->returnValue($checkOutCartResult));

        $this->clientApi->setService($serviceMock);

        $client = new \Model_Client();
        $client->loadBean(new \DummyBean());

        $this->clientApi->setIdentity($client);

        $data   = array(
            'id' => rand(1, 100)
        );
        $di = new \Pimple\Container();

        $this->clientApi->setDi($di);
        $result = $this->clientApi->checkout($data);

        $this->assertIsArray($result);
    }
}
 