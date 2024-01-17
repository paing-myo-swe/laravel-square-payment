<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCardPaymentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Square\Models\Builders\CardBuilder;
use Square\Models\Builders\CreateCardRequestBuilder;
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

    // public function showPaymentForm(Request $request)
    // {
    //     $appId = config('services.square.app_id');
    //     $locationId = config('services.square.location_id');

    //     return view('square.card-payment', compact([
    //         'appId',
    //         'locationId',
    //     ]));
    // }

    // public function createPayment(CreateCardPaymentRequest $request)
    // {
    //     try {

    //         $referenceId = Str::uuid(); //custom generate unique id to track
    //         $idempotencyKey = Str::uuid();

    //         $sourceId = $request->sourceId;
    //         $locationId = $request->locationId;

    //         // While it's tempting to pass this data from the client
    //         // Doing so allows bad actor to modify these values
    //         // Instead, leverage Orders to create an order on the server
    //         // and pass the Order ID to createPayment rather than raw amounts
    //         // See Orders documentation: https://developer.squareup.com/docs/orders-api/what-it-does
    //         $amount = 1;

    //         $body = CreatePaymentRequestBuilder::init(
    //             $sourceId, //'cnon:card-nonce-ok' this source_id repersent to show card form
    //             $idempotencyKey //unique key
    //         )
    //             ->amountMoney(
    //                 MoneyBuilder::init()
    //                     // the expected amount is in cents, meaning this is $1.00.
    //                     ->amount($amount . "00")
    //                     // If you are a non-US account, you must change the currency to match the country in which
    //                     // you are accepting the payment.
    //                     ->currency(Currency::USD)
    //                     ->build()
    //             )
    //             // ->appFeeMoney(
    //             //     MoneyBuilder::init()
    //             //         ->amount(10)
    //             //         ->currency(Currency::USD)
    //             //         ->build()
    //             // )
    //             ->autocomplete(true)
    //             // ->customerId('')
    //             ->locationId($locationId)
    //             ->referenceId($referenceId)
    //             ->note('Brief description')
    //             ->build();
            
    //         $apiResponse = $this->client->getPaymentsApi()->createPayment($body);
    //         return $this->handleApiResponse($apiResponse);
        
    //     } catch (ApiException $e) {
    //         return response()->json($e->getMessage());
    //     }
    // }

    public function myCards(Request $request)
    {
        $user = User::find(1);

        try {
            $apiResponse = $this->client->getCardsApi()->listCards(null, $user->square_customer_id);
            $cards = $this->handleApiResponse($apiResponse)->getData()->cards;

            return view('square.cards', compact('cards'));
        
        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function showCardForm()
    {
        $appId = config('services.square.app_id');
        $locationId = config('services.square.location_id');

        return view('square.card-create', compact([
            'appId',
            'locationId',
        ]));
    }

    public function addNewCard(Request $request)
    {
        $user = User::find(1);

        try {

            $customerId = $user->square_customer_id;
            $cardholderName = $user->name;

            $referenceId = Str::uuid();
            $idempotencyKey = Str::uuid();

            $sourceId = $request->sourceId;

            $body = CreateCardRequestBuilder::init(
                $idempotencyKey,
                $sourceId,
                CardBuilder::init()
                    ->cardholderName($cardholderName)
                    // ->billingAddress(
                    //     AddressBuilder::init()
                    //         ->addressLine1('500 Electric Ave')
                    //         ->addressLine2('Suite 600')
                    //         ->locality('New York')
                    //         ->administrativeDistrictLevel1('NY')
                    //         ->postalCode('10003')
                    //         ->country(Country::US)
                    //         ->build()
                    // )
                    ->customerId($customerId)
                    ->referenceId($referenceId)
                    ->build()
            )->build();
            
            $apiResponse = $this->client->getCardsApi()->createCard($body);
            
            if ($apiResponse->isSuccess()) {
                $createCardResponse = $apiResponse->getResult();
            } else {
                $errors = $apiResponse->getErrors();
            }
            
            return $this->handleApiResponse($apiResponse);

        } catch (ApiException $e) {
            return response()->json($e->getMessage());
        }
    }

    public function pay(Request $request)
    {
        $user = User::find(1);

        try {

            $referenceId = Str::uuid();
            $idempotencyKey = Str::uuid();

            $sourceId = $request->cardId;
            $locationId = config('services.square.location_id');
            $customerId = $user->square_customer_id;

            $amount = 1;

            $body = CreatePaymentRequestBuilder::init(
                $sourceId,
                $idempotencyKey
            )
                ->amountMoney(
                    MoneyBuilder::init()
                        ->amount($amount . "00")
                        ->currency(Currency::USD)
                        ->build()
                )
                ->autocomplete(true)
                ->customerId($customerId)
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
