<?php
/**
 * Copyright 2017 Lalamove
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @description
 *
 * @author Draco <yamdraco@gmail.com>
 */
namespace Lalamove\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Request
{
  public $method = "GET";
  public $body = array();
  public $host = '';
  public $path = '';
  public $header = array();

  public $key = '';
  public $secret = '';
  public $country = '';

  public $ch = null;

  /**
   * Create the signature for the
   *
   * @param $time , time to create the signature (should use current time, same as the Authorization timestamp)
   *
   * @return string a signed signature using the secret
   */
  public function getSignature($time)
  {
    $_encryptBody = '';
    if ($this->method == "GET") {
      $_encryptBody = $time . "\r\n" . $this->method . "\r\n" . $this->path . "\r\n\r\n";
    } else {
      $_encryptBody = $time . "\r\n" . $this->method . "\r\n" . $this->path . "\r\n\r\n" . json_encode((object)$this->body);
    }

    return hash_hmac("sha256", $_encryptBody, $this->secret);
  }

  /**
   * Build and return the header require for calling lalamove API
   *
   * @return array an associative aray of lalamove header
   */
  public function buildHeader()
  {
    $time = time() * 1000;

    return [
      "X-Request-ID" => uniqid(),
      "Content-type" => "application/json; charset=utf-8",
      "Authorization" => "hmac " . $this->key . ":" . $time . ":" . $this->getSignature($time),
      "Accept" => "application/json",
      "X-LLM-Country" => $this->country
    ];
  }

  /**
   * Send out the request via guzzleHttp
   *
   * @return ResponseInterface return the result after requesting through guzzleHttp
   * @throws GuzzleException
   */
  public function send()
  {
    $client = new Client();

    $content = [
      'headers' => $this->buildHeader(),
      'http_errors' => false
    ];
    if ($this->method != "GET") {
      $content['json'] = (object)$this->body;
    }

    return $client->request($this->method, $this->host . $this->path, $content);
  }
}

class LalamoveApi
{
  public $host = '';
  public $key = '';
  public $secret = '';

  public $country = '';

  /**
   * Constructor for Lalamove API
   *
   * @param string $host domain with http / https
   * @param string $apiKey apikey lalamove provide
   * @param string $apiSecret apisecret lalamove provide
   * @param string $country two letter country code such as HK, TH, SG
   */
  public function __construct($host = "", $apiKey = "", $apiSecret = "", $country = "")
  {
    $this->host = $host;
    $this->key = $apiKey;
    $this->secret = $apiSecret;
    $this->country = $country;
  }

  /**
   * Make a http Request to get a quotation from lalamove API via guzzlehttp/guzzle
   *
   * @param $body {Object}, the body of the json
   * @return ResponseInterface the http response from guzzlehttp/guzzle, an exception will not be thrown
   *   2xx - http request is successful
   *   4xx - unsuccessful request, see body for error message and documentation for matching
   *   5xx - server error, please contact lalamove
   * @throws GuzzleException
   */
  public function quotation($body)
  {
    $request = new Request();
    $request->method = "POST";
    $request->path = "/v2/quotations";
    $request->body = $body;
    $request->host = $this->host;
    $request->key = $this->key;
    $request->secret = $this->secret;
    $request->country = $this->country;

    return $request->send();
  }

  /**
   * Make a http request to place an order at lalamove API via guzzlehttp/guzzle
   *
   * @param array $body the body of the json
   * @return ResponseInterface the http response from guzzlehttp/guzzle, an exception will not be thrown
   *   2xx - http request is successful
   *   4xx - unsuccessful request, see body for error message and documentation for matching
   *   5xx - server error, please contact lalamove
   * @throws GuzzleException
   */
  public function postOrder($body)
  {
    $request = new Request();
    $request->method = "POST";
    $request->path = "/v2/orders";
    $request->body = $body;
    $request->host = $this->host;
    $request->key = $this->key;
    $request->secret = $this->secret;
    $request->country = $this->country;

    return $request->send();
  }

  /**
   * Make a http request to get the status of order
   *
   * @param string $orderId the customerOrderId of lalamove
   * @return ResponseInterface the http response from guzzlehttp/guzzle, an exception will not be thrown
   *   2xx - http request is successful
   *   4xx - unsuccessful request, see body for error message and documentation for matching
   *   5xx - server error, please contact lalamove
   * @throws GuzzleException
   */
  public function getOrderStatus($orderId)
  {
    $request = new Request();
    $request->method = "GET";
    $request->path = "/v2/orders/" . $orderId;
    $request->host = $this->host;
    $request->key = $this->key;
    $request->secret = $this->secret;
    $request->country = $this->country;

    return $request->send();
  }

  /**
   * Make a http request to get the driver Info
   *
   * @param string $orderId the customerOrderId of lalamove
   * @param string $driverId
   * @return ResponseInterface the http response from guzzlehttp/guzzle, an exception will not be thrown
   *   2xx - http request is successful
   *   4xx - unsuccessful request, see body for error message and documentation for matching
   *   5xx - server error, please contact lalamove
   * @throws GuzzleException
   */
  public function getDriverInfo($orderId, $driverId)
  {
    $request = new Request();
    $request->method = "GET";
    $request->path = "/v2/orders/" . $orderId . "/drivers/" . $driverId;
    $request->host = $this->host;
    $request->key = $this->key;
    $request->secret = $this->secret;
    $request->country = $this->country;

    return $request->send();
  }

  /**
   * Make a http request to get the driver Location
   *
   * @param string $orderId the customerOrderId of lalamove
   * @param string $driverId the id of the driver at lalamove
   * @return ResponseInterface the http response from guzzlehttp/guzzle, an exception will not be thrown
   *   2xx - http request is successful
   *   4xx - unsuccessful request, see body for error message and documentation for matching
   *   5xx - server error, please contact lalamove
   * @throws GuzzleException
   */
  public function getDriverLocation($orderId, $driverId)
  {
    $request = new Request();
    $request->method = "GET";
    $request->path = "/v2/orders/" . $orderId . "/drivers/" . $driverId . "/location";
    $request->host = $this->host;
    $request->key = $this->key;
    $request->secret = $this->secret;
    $request->country = $this->country;

    return $request->send();
  }

  /**
   * Cancel the http request to get the driver location
   *
   * @param string $orderId the customerOrderId of lalamove
   * @return ResponseInterface the http response from guzzlehttp/guzzle, an exception will not be thrown
   *   2xx - http request is successful
   *   4xx - unsuccessful request, see body for error message and documentation for matching
   *   5xx - server error, please contact lalamove
   * @throws GuzzleException
   */
  public function cancelOrder($orderId)
  {
    $request = new Request();
    $request->method = "PUT";
    $request->path = "/v2/orders/" . $orderId . "/cancel";
    $request->host = $this->host;
    $request->key = $this->key;
    $request->secret = $this->secret;
    $request->country = $this->country;

    return $request->send();
  }
}
