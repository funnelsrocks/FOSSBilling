<?php


namespace Box\Mod\Invoice;


class ServiceTransactionTest extends \BBTestCase
{
    /**
     * @var \Box\Mod\Invoice\ServiceTransaction
     */
    protected $service = null;

    public function setup(): void
    {
        $this->service = new \Box\Mod\Invoice\ServiceTransaction();
    }

    public function testgetDi()
    {
        $di = new \Pimple\Container();
        $this->service->setDi($di);
        $getDi = $this->service->getDi();
        $this->assertEquals($di, $getDi);
    }

    public function testproccessReceivedATransactions()
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $serviceMock = $this->getMockBuilder('\Box\Mod\Invoice\ServiceTransaction')
            ->setMethods(array('getReceived', 'preProcessTransaction'))
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('getReceived')
            ->will($this->returnValue(array(array())));
        $serviceMock->expects($this->atLeastOnce())
            ->method('preProcessTransaction');

        $dbMock = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->will($this->onConsecutiveCalls($transactionModel));

        $di           = new \Pimple\Container();
        $di['logger'] = new \Box_Log();
        $di['db']     = $dbMock;

        $serviceMock->setDi($di);
        $result = $serviceMock->proccessReceivedATransactions();
        $this->assertTrue($result);
    }

    public function testupdate()
    {
        $eventsMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventsMock->expects($this->atLeastOnce())
            ->method('fire');

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $dbMock = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di                   = new \Pimple\Container();
        $di['db']             = $dbMock;
        $di['events_manager'] = $eventsMock;
        $di['logger']         = new \Box_Log();

        $this->service->setDi($di);

        $data   = array(
            'invoice_id'   => 1,
            'txn_id'       => 2,
            'txn_status'   => '',
            'gateway_id'   => 1,
            'amount'       => '',
            'currency'     => '',
            'type'         => '',
            'note'         => '',
            'status'       => '',
            'validate_ipn' => '',
        );
        $result = $this->service->update($transactionModel, $data);
        $this->assertTrue($result);
    }

    public function testcreate()
    {
        $eventsMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventsMock->expects($this->atLeastOnce())
            ->method('fire');

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $invoiceModel = new \Model_Invoice();
        $invoiceModel->loadBean(new \DummyBean());

        $payGatewayModel = new \Model_PayGateway();
        $payGatewayModel->loadBean(new \DummyBean());
        $dbMock = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->will($this->onConsecutiveCalls($invoiceModel, $payGatewayModel));
        $dbMock->expects($this->atLeastOnce())
            ->method('dispense')
            ->will($this->returnValue($transactionModel));

        $newId = 2;
        $dbMock->expects($this->atLeastOnce())
            ->method('store')
            ->will($this->returnValue($newId));

        $requestMock = $this->getMockBuilder('\FOSSBilling\Request')->getMock();
        $requestMock->expects($this->atLeastOnce())
            ->method('getClientAddress');

        $di                   = new \Pimple\Container();
        $di['db']             = $dbMock;
        $di['events_manager'] = $eventsMock;
        $di['request']        = $requestMock;
        $di['logger']         = new \Box_Log();

        $this->service->setDi($di);

        $data   = array(
            'skip_validation' => false,
            'bb_gateway_id'   => 1,
            'bb_invoice_id'   => 2,
        );
        $result = $this->service->create($data);
        $this->assertIsInt($result);
        $this->assertEquals($newId, $result);
    }

    public function testcreateInvalidMissinginvoice_id()
    {
        $eventsMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventsMock->expects($this->atLeastOnce())
            ->method('fire');

        $di                   = new \Pimple\Container();
        $di['events_manager'] = $eventsMock;

        $this->service->setDi($di);

        $data = array(
            'skip_validation' => false,
            'bb_gateway_id'   => 1,
        );

        $this->expectException(\FOSSBilling\Exception::class);
        $this->expectExceptionMessage('Transaction invoice id is missing');
        $this->service->create($data);
    }

    public function testcreateInvalidMissingbb_gateway_id()
    {
        $eventsMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventsMock->expects($this->atLeastOnce())
            ->method('fire');

        $di                   = new \Pimple\Container();
        $di['events_manager'] = $eventsMock;

        $this->service->setDi($di);

        $data = array(
            'skip_validation' => false,
            'bb_invoice_id'   => 2,
        );

        $this->expectException(\FOSSBilling\Exception::class);
        $this->expectExceptionMessage('Payment gateway id is missing');
        $this->service->create($data);
    }

    public function testdelete()
    {
        $dbMock = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('trash');

        $di           = new \Pimple\Container();
        $di['logger'] = new \Box_Log();
        $di['db']     = $dbMock;
        $this->service->setDi($di);

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $result = $this->service->delete($transactionModel);
        $this->assertTrue($result);
    }

    public function testtoApiArray()
    {
        $dbMock          = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $payGatewayModel = new \Model_PayGateway();
        $payGatewayModel->loadBean(new \DummyBean());
        $dbMock->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue($payGatewayModel));
        $di       = new \Pimple\Container();
        $di['db'] = $dbMock;
        $this->service->setDi($di);

        $expected         = array(
            'id'           => null,
            'invoice_id'   => null,
            'txn_id'       => null,
            'txn_status'   => null,
            'gateway_id'   => 1,
            'gateway'      => null,
            'amount'       => null,
            'currency'     => null,
            'type'         => null,
            'status'       => null,
            'ip'           => null,
            'validate_ipn' => null,
            'error'        => null,
            'error_code'   => null,
            'note'         => null,
            'created_at'   => null,
            'updated_at'   => null,
        );
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $transactionModel->gateway_id = 1;

        $result = $this->service->toApiArray($transactionModel, false);
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    public function searchQueryData()
    {
        return array(
            array(
                array(), array(), 'SELECT m.*',
            ),
            array(
                array('search' => 'keyword'), array('note' => '%keyword%', 'search__invoice_id' => '%keyword%', 'search_txn_id' => '%keyword%', 'ipn' => '%keyword%'), 'AND m.note LIKE :note OR m.invoice_id LIKE :search_invoice_id OR m.txn_id LIKE :search_txn_id OR m.ipn LIKE :ipn',
            ),
            array(
                array('invoice_hash' => 'hashString'), array('hash' => 'hashString'), 'AND i.hash = :hash',
            ),
            array(
                array('invoice_id' => '1'), array('invoice_id' => '1'), 'AND m.invoice_id = :invoice_id',
            ),
            array(
                array('gateway_id' => '2'), array('gateway_id' => '2'), 'AND m.gateway_id = :gateway_id',
            ),
            array(
                array('client_id' => '3'), array('client_id' => '3'), 'AND i.client_id = :client_id',
            ),
            array(
                array('status' => 'active'), array('status' => 'active'), 'AND m.status = :status',
            ),
            array(
                array('currency' => 'Eur'), array('currency' => 'Eur'), 'AND m.currency = :currency',
            ),
            array(
                array('type' => 'payment'), array('type' => 'payment'), 'AND m.type = :type',
            ),
            array(
                array('txn_id' => 'longTxn_id'), array('txn_id' => 'longTxn_id'), 'AND m.txn_id = :txn_id',
            ),
            array(
                array('date_from' => '2012-12-12'), array('date_from' => 1355270400), 'AND UNIX_TIMESTAMP(m.created_at) >= :date_from',
            ),
            array(
                array('date_to' => '2012-12-12'), array('date_to' => 1355270400), 'AND UNIX_TIMESTAMP(m.created_at) <= :date_to',
            ),
        );
    }

    /**
     * @dataProvider searchQueryData
     */
    public function testgetSearchQuery($data, $expectedParams, $expectedStringPart)
    {
        $di = new \Pimple\Container();

        $this->service->setDi($di);
        $result = $this->service->getSearchQuery($data);
        $this->assertIsString($result[0]);
        $this->assertIsArray($result[1]);

        $this->assertTrue(strpos($result[0], $expectedStringPart) !== false);
        $this->assertEquals($expectedParams, $result[1]);
    }

    public function testcounter()
    {
        $queryResult = array(array('status' => \Model_Transaction::STATUS_RECEIVED, 'counter' => 1));
        $dbMock      = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getAll')
            ->will($this->returnValue($queryResult));

        $di       = new \Pimple\Container();
        $di['db'] = $dbMock;
        $this->service->setDi($di);

        $result = $this->service->counter();
        $this->assertIsArray($result);
        $expected = array(
            'total'     => 1,
            'received'  => 1,
            'approved'  => 0,
            'error'     => 0,
            'processed' => 0
        );
        $this->assertEquals($expected, $result);
    }

    public function testgetStatusPairs()
    {
        $result = $this->service->getStatusPairs();
        $this->assertIsArray($result);

        $expected = array(
            'received'  => 'Received',
            'approved'  => 'Approved',
            'processed' => 'Processed',
            'error'     => 'Error'
        );
        $this->assertEquals($expected, $result);
    }

    public function testgetStatus()
    {
        $result = $this->service->getStatuses();
        $this->assertIsArray($result);

        $expected = array(
            'received'  => 'Received',
            'approved'  => 'Approved/Verified',
            'processed' => 'Processed',
            'error'     => 'Error'
        );
        $this->assertEquals($expected, $result);
    }

    public function testgetGatewayStatuses()
    {
        $result = $this->service->getGatewayStatuses();
        $this->assertIsArray($result);

        $expected = array(
            'pending'  => 'Pending validation',
            'complete' => 'Complete',
            'unknown'  => 'Unknown',
        );
        $this->assertEquals($expected, $result);
    }

    public function testgetTypes()
    {
        $result = $this->service->getTypes();
        $this->assertIsArray($result);

        $expected = array(
            'payment'             => 'Payment',
            'refund'              => 'Refund',
            'subscription_create' => 'Subscription create',
            'subscription_cancel' => 'Subscription cancel',
            'unknown'             => 'Unknown',
        );
        $this->assertEquals($expected, $result);
    }

    public function testpreProcessTransaction()
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $serviceMock = $this->getMockBuilder('\Box\Mod\Invoice\ServiceTransaction')
            ->setMethods(array('processTransaction'))
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('processTransaction')
            ->will($this->returnValue('processedOutputString'));

        $eventMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventMock->expects($this->atLeastOnce())
            ->method('fire');


        $di                   = new \Pimple\Container();
        $di['events_manager'] = $eventMock;
        $di['logger']         = new \Box_Log();
        $serviceMock->setDi($di);

        $result = $serviceMock->preProcessTransaction($transactionModel);
        $this->assertIsString($result);
    }

    public function testpreProcessTransaction_registerException()
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $exceptionMessage = 'Exception created with PHPUnit Test';

        $serviceMock = $this->getMockBuilder('\Box\Mod\Invoice\ServiceTransaction')
            ->setMethods(array('processTransaction', 'oldProcessLogic'))
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('processTransaction')
            ->will($this->throwException(new \FOSSBilling\Exception($exceptionMessage)));

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di       = new \Pimple\Container();
        $di['db'] = $dbMock;
        $serviceMock->setDi($di);

        $this->expectException(\FOSSBilling\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $serviceMock->preProcessTransaction($transactionModel);
    }

    public function paymentsAdapterProvider_withprocessTransaction()
    {
        return array(
            array('\Payment_Adapter_PayPalEmail'),
        );
    }

    /**
     * @dataProvider paymentsAdapterProvider_withprocessTransaction
     */
    public function testprocessTransaction_supportProcessTransaction($adapter)
    {
        $id               = 1;
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $transactionModel->gateway_id = 2;
        $transactionModel->ipn        = '{}';

        $payGatewayModel = new \Model_PayGateway();
        $payGatewayModel->loadBean(new \DummyBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->onConsecutiveCalls($transactionModel, $payGatewayModel));

        $paymentAdapterMock = $this->getMockBuilder($adapter)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentAdapterMock->expects($this->atLeastOnce())
            ->method('processTransaction');

        $payGatewayService = $this->getMockBuilder('\Box\Mod\Invoice\ServicePayGateway')->getMock();
        $payGatewayService->expects($this->atLeastOnce())
            ->method('getPaymentAdapter')
            ->will($this->returnValue($paymentAdapterMock));

        $di                = new \Pimple\Container();
        $di['db']          = $dbMock;
        $di['mod_service'] = $di->protect(function () use ($payGatewayService) { return $payGatewayService; });
        $di['api_system']  = new \Api_Handler(new \Model_Admin());
        $this->service->setDi($di);

        $this->service->processTransaction($id);
    }

    public function getReceived()
    {
        $serviceMock = $this->getMockBuilder('\Box\Mod\Invoice\ServiceTransaction')
            ->setMethods(array('getSearchQuery'))
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('getSearchQuery')
            ->will($this->returnValue(array('SqlString', array())));

        $assoc  = array(
            array(
                'id'         => 1,
                'invoice_id' => 1,
            ),
        );
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue($assoc));

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $dbMock->expects($this->atLeastOnce())
            ->method('getAll')
            ->will($this->returnValue(array(array())));

        $di       = new \Pimple\Container();
        $di['db'] = $dbMock;
        $serviceMock->setDi($di);

        $result = $serviceMock->getReceived();
        $this->assertIsArray($result);
    }

    public function testdebitTransaction()
    {
        $currency     = 'EUR';
        $invoiceModel = new \Model_Invoice();
        $invoiceModel->loadBean(new \DummyBean());
        $invoiceModel->currency = $currency;

        $clientModdel = new \Model_Client();
        $clientModdel->loadBean(new \DummyBean());
        $clientModdel->currency = $currency;

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $transactionModel->amount = 11;

        $clientBalanceModel = new \Model_ClientBalance();
        $clientBalanceModel->loadBean(new \DummyBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->onConsecutiveCalls($invoiceModel, $clientModdel));
        $dbMock->expects($this->atLeastOnce())
            ->method('dispense')
            ->will($this->returnValue($clientBalanceModel));
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di       = new \Pimple\Container();
        $di['db'] = $dbMock;
        $this->service->setDi($di);

        $this->service->debitTransaction($transactionModel);
    }


    public function testcreateAndProcess()
    {
        $serviceMock = $this->getMockBuilder('\Box\Mod\Invoice\ServiceTransaction')
            ->setMethods(array('create', 'processTransaction'))
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('create');
        $serviceMock->expects($this->once())
            ->method('processTransaction');

        $ipn = array();
        $serviceMock->createAndProcess($ipn);
    }


}

