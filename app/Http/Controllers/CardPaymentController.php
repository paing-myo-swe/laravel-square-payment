<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCardPaymentRequest;
use Illuminate\Http\Request;
use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;
use Square\Models\Builders\MoneyBuilder;
use Square\Models\Builders\CreatePaymentRequestBuilder;
use Square\Models\Currency;
use Illuminate\Support\Str;

class CardPaymentController extends Controller
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

    public function showPaymentForm(Request $request)
    {
        $idempotencyKey = Str::uuid();
        $appId = config('services.square.app_id');
        $locationId = config('services.square.location_id');
        $amount = 1;

        return view('square.card-payment', compact([
            'idempotencyKey',
            'appId',
            'locationId',
            'amount'
        ]));
    }

    public function createPayment(CreateCardPaymentRequest $request)
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
            $amount = 1;

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
}
