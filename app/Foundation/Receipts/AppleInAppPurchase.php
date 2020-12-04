<?php

namespace App\Foundation\Receipts;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class AppleInAppPurchase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * AppleInAppPurchase constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $receiptData
     * @param $site
     * @param $environment
     * @return array
     */
    public function verifyReceipt($receiptData, $site, $environment): array
    {
        $endpoint = $this->getEndpoint($environment);

        $headers = ['Content-Type' => 'application/json'];

        $body = json_encode([
            'receipt-data' => $receiptData,
            'password' => $this->getSecret($site)
        ]);

        $request = new Request('POST', $endpoint, $headers, $body);

        $promise = $this->client->sendAsync($request)
            ->then(function (ResponseInterface $response) {
                return json_decode($response->getBody()->getContents(), true);
            }, function (RequestException $exception) {
                Log::error($exception->getMessage());
                return ['status' => 21011];
            });

        return $promise->wait();
    }

    /**
     * @param string $environment
     * @return string
     */
    private function getEndpoint(string $environment): string
    {
        return config("apple.verify_receipt.endpoint.{$environment}");
    }

    /**
     * @param string $site
     * @return string
     */
    private function getSecret(string $site): string
    {
        return config("apple.verify_receipt.secret.{$site}");
    }
}
