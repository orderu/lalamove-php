<?php

use Lalamove\Api\LalamoveApi;
use PHPUnit\Framework\TestCase;

if (!getenv('country')) {
  $Loader = new \josegonzalez\Dotenv\Loader('.env');
  // Parse the .env file
  $Loader->parse();
  // Send the parsed .env file to the $_ENV variable
  $Loader->putenv();
}

class LalamoveTest extends TestCase
{
  public function generateRandomString($length = 10)
  {
    $x = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    return substr(str_shuffle(str_repeat($x, ceil($length / strlen($x)))), 1, $length);
  }

  public $body = [
    "serviceType" => "MOTORCYCLE",
    "specialRequests" => [],
    "requesterContact" => [
      "name" => "Draco Yam",
      "phone" => "+60376886555"
    ],
    "stops" => [
      [
        "location" => ["lat" => "3.1485313", "lng" => "101.6092694"],
        "addresses" => [
          "en_MY" => [
            "displayString" => 'Jalan BU 3/5, Bandar Utama, 47800 Petaling Jaya, Selangor, Malaysia',
            "country" => "MY"
          ]
        ]
      ],
      [
        "location" => ["lat" => "3.1511957", "lng" => "101.6107607"],
        "addresses" => [
          "en_MY" => [
            "displayString" => "142, Jalan BU 3/2, Bandar Utama, 47800 Petaling Jaya, Selangor, Malaysia",
            "country" => "MY"
          ]
        ]
      ]
    ],
    "deliveries" => [
      [
        "toStop" => 1,
        "toContact" => [
          "name" => "Brian Garcia",
          "phone" => "+60376886559"
        ],
        "remarks" => "ORDER #: 1234, ITEM 1 x 1, ITEM 2 x 2"
      ]
    ]
  ];

  public function testAuthFail()
  {
    $request = new LalamoveApi(getenv('host'), 'abc123', 'abc123', getenv('country'));
    $result = $request->quotation($this->body);

    $content = (string)$result->getBody();
    self::assertSame($result->getStatusCode(), 401);
  }

  public function testQuotation()
  {
    $results = [];
    $scheduleAt = gmdate('Y-m-d\TH:i:s\Z', time() + 60 * 30);
    $this->body['scheduleAt'] = $scheduleAt;
    $this->body['deliveries'][0]['remarks'] = $this->generateRandomString();
    $request = new LalamoveApi(getenv('host'), getenv('key'), getenv('secret'), getenv('country'));

    $result = $request->quotation($this->body);

    self::assertSame($result->getStatusCode(), 200);

    $content = json_decode($result->getBody()->getContents());

    $results['scheduleAt'] = $scheduleAt;
    $results['quotation'] = $content;

    return $results;
  }

  /**
   * @depends testQuotation
   */
  public function testPostOrder($results)
  {
    $request = new LalamoveApi(getenv('host'), getenv('key'), getenv('secret'), getenv('country'));
    $this->body['scheduleAt'] = $results['scheduleAt'];
    $this->body['quotedTotalFee'] = array(
      'amount' => $results['quotation']->totalFee,
      'currency' => $results['quotation']->totalFeeCurrency
    );
    // too frequent submission of the same body will cause 429 error
    // therefore adding random string inside the remark when testing
    $this->body['deliveries'][0]['remarks'] = $this->generateRandomString();
    $result = $request->postOrder($this->body);
    self::assertSame($result->getStatusCode(), 200);

    $results['orderId'] = json_decode($result->getBody()->getContents());

    return $results;
  }

  /**
   * @depends testPostOrder
   */
  public function testGetOrderStatus($results)
  {
    $request = new LalamoveApi(getenv('host'), getenv('key'), getenv('secret'), getenv('country'));
    $result = $request->getOrderStatus($results['orderId']->customerOrderId);
    self::assertSame($result->getStatusCode(), 200);
  }

  /**
   * @depends testPostOrder
   */
  public function testCancelOrder($results)
  {
    $request = new LalamoveApi(getenv('host'), getenv('key'), getenv('secret'), getenv('country'));
    $result = $request->cancelOrder($results['orderId']->customerOrderId);
    self::assertSame($result->getStatusCode(), 200);
  }

  public function testGetExistingOrderStatus()
  {
    $request = new LalamoveApi(getenv('host'), getenv('key'), getenv('secret'), getenv('country'));
    $result = $request->getOrderStatus(getenv('orderId'));
    self::assertSame($result->getStatusCode(), 200);
  }

//  public function testGetDriverInfo()
//  {
//    $request = new LalamoveApi(getenv('host'), getenv('key'), getenv('secret'), getenv('country'));
//    $result = $request->getDriverInfo(getenv('orderId'), '21712');
//    self::assertSame($result->getStatusCode(), 200);
//  }

//  public function testGetDriverLocation()
//  {
//    $body = [
//      "location" => [
//        "lat" => "13.740167",
//        "lng" => "100.535237"
//      ],
//      "updatedAt" => "2017-12-01T14:30.00Z"
//    ];
//
//    $response = new Guzzle\Http\Message\Response(200, [], json_encode((object)$body));
//
//    $mock = Mockery::mock(LalamoveApi::class);
//    $mock
//      ->shouldReceive('getDriverLocation')
//      ->with(getenv('orderId'), '21712')
//      ->andReturn($response);
//
//    $result = $mock->getDriverLocation(getenv('orderId'), '21712');
//    self::assertSame($result->getStatusCode(), 200);
//    self::assertSame($result->json()['location']['lat'], "13.740167");
//    self::assertSame($result->json()['updatedAt'], "2017-12-01T14:30.00Z");
//  }
}
