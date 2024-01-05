<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;
use Square\Models\Builders\CreatePaymentLinkRequestBuilder;
use Square\Models\Builders\QuickPayBuilder;
use Square\Models\Builders\MoneyBuilder;
use Square\Models\Builders\CreateOrderRequestBuilder;
use Square\Models\Builders\OrderBuilder;
use Square\Models\Builders\OrderLineItemBuilder;
use Square\Models\Builders\CreatePaymentRequestBuilder;
use Square\Models\Currency;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new SquareClient([
            'accessToken' => config('services.square.access_token'),
            'environment' => Environment::SANDBOX,
        ]);
    }

    public function handleApiResponse($apiResponse)
    {
        if ($apiResponse->isSuccess()) {
            $result = $apiResponse->getResult();
            return response()->json($result);
    
        } else {
            $errors = $apiResponse->getErrors();
            return response()->json($errors);
        }
    }

    public function getLocations(Request $request)
    {
        try {

            $apiResponse = $this->client->getLocationsApi()->listLocations();
            return $this->handleApiResponse($apiResponse);

        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getCustomers(Request $request)
    {
        try {

            $count = false;
            $apiResponse = $this->client->getCustomersApi()->listCustomers(null, null, null, null, $count);
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getCards(Request $request)
    {
        try {

            $apiResponse = $this->client->getCardsApi()->listCards();
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getPayments(Request $request)
    {
        try {

            $apiResponse = $this->client->getPaymentsApi()->listPayments();
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function createPayment(Request $request)
    {
        try {

            $referenceId = Str::uuid(); //custom generate unique id to track
            $idempotencyKey = $request->idempotencyKey;
            $sourceId = $request->sourceId;
            $locationId = $request->locationId;

            // While it's tempting to pass this data from the client
            // Doing so allows bad actor to modify these values
            // Instead, leverage Orders to create an order on the server
            // and pass the Order ID to createPayment rather than raw amounts
            // See Orders documentation: https://developer.squareup.com/docs/orders-api/what-it-does
            $amount = $request->amount;

            $body = CreatePaymentRequestBuilder::init(
                $sourceId, //'cnon:card-nonce-ok' this source_id repersent to show card form
                $idempotencyKey //unique key
            )
                ->amountMoney(
                    MoneyBuilder::init()
                        // the expected amount is in cents, meaning this is $1.00.
                        ->amount($amount . "00")
                        // If you are a non-US account, you must change the currency to match the country in which
                        // you are accepting the payment.
                        ->currency(Currency::USD)
                        ->build()
                )
                // ->appFeeMoney(
                //     MoneyBuilder::init()
                //         ->amount(10)
                //         ->currency(Currency::USD)
                //         ->build()
                // )
                ->autocomplete(true)
                // ->customerId('')
                ->locationId($locationId)
                ->referenceId($referenceId)
                ->note('Brief description')
                ->build();
            
            $apiResponse = $this->client->getPaymentsApi()->createPayment($body);
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getPaymentLinks(Request $request)
    {
        try {

            $apiResponse = $this->client->getCheckoutApi()->listPaymentLinks();
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getPaymentLinkDetails(Request $request, $id)
    {
        try {

            $apiResponse = $this->client->getCheckoutApi()->retrievePaymentLink($id);
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function createPaymentLink(Request $request)
    {
        try {

            $idempotencyKey = Str::uuid();

            $body = CreatePaymentLinkRequestBuilder::init()
                ->idempotencyKey($idempotencyKey) //unique key genrate
                ->quickPay(
                    QuickPayBuilder::init(
                        'Auto Detailing', //product name
                        MoneyBuilder::init()
                            ->amount(10000)
                            ->currency(Currency::USD)
                            ->build(),
                        config('services.square.location_id') //location id
                    )->build()
                )->build();

            $apiResponse = $this->client->getCheckoutApi()->createPaymentLink($body);
            return $this->handleApiResponse($apiResponse);

        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function createOrder(Request $request)
    {
        try {

            $idempotencyKey = Str::uuid();
            $referenceId = Str::uuid();

            $body = CreateOrderRequestBuilder::init()
                ->order(
                    OrderBuilder::init(
                        config('services.square.location_id') //location id
                    )
                        ->referenceId($referenceId) //generated order reference id
                        ->lineItems(
                            [
                                OrderLineItemBuilder::init(
                                    '1'
                                )
                                    ->name('New York Strip Steak') //order item name
                                    ->basePriceMoney(
                                        MoneyBuilder::init()
                                            ->amount(1599)
                                            ->currency(Currency::USD)
                                            ->build()
                                    )
                                    ->build(),
                            ]
                        )
                        ->build()
                )
                ->idempotencyKey($idempotencyKey)
                ->build();

            $apiResponse = $this->client->getOrdersApi()->createOrder($body);
            return $this->handleApiResponse($apiResponse);

        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getOrderDetails(Request $request, $id)
    {
        try {

            $apiResponse = $this->client->getOrdersApi()->retrieveOrder($id);
            return $this->handleApiResponse($apiResponse);
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }
}
