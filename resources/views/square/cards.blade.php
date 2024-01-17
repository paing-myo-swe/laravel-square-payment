<!doctype html>
<html lange="en">
  <head>
    <meta charset="utf-8" />
    <title>Cards List</title>
    <link href="{{ asset('square/card-payment.css') }}" rel="stylesheet" />
  </head>

  <body>
    <form id="payment-form" method="POST" action="{{ route('pay') }}">
      @csrf
      <div id="card-container">
        <div>
          <h3>Your Cards List</h3>
          <a href="{{ route('cards.create') }}">Add New Card</a>
        </div>
        <ul>
          @foreach ($cards as $card)
            <li><input type="radio" name="cardId" value="{{ $card->id }}">
              Card Id: {{ $card->id }},
              Card Brand: {{ $card->card_brand }},
              Last 4: {{ $card->last_4 }}, CardHolder Name: {{ $card->cardholder_name }}, Exp: {{ $card->exp_month }}/{{ $card->exp_year }} 
            </li>
          @endforeach
        </ul>
      </div>
      <button id="card-button" type="submit">Confirm</button>
    </form>
    <div id="payment-status-container"></div>
  </body>

<script>
</script>
</html>
