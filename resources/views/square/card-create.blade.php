<!doctype html>
<html lange="en">
  <head>
    <script
      type="text/javascript"
      src="https://sandbox.web.squarecdn.com/v1/square.js"
    ></script>
    <meta charset="utf-8" />
    <title>Create New Card</title>
    <link href="{{ asset('square/card-payment.css') }}" rel="stylesheet" />
  </head>

  <body>
    <form id="payment-form">
      <div id="card-container"></div>
      <button id="card-button" type="button">Save Card</button>
    </form>
    <div id="payment-status-container"></div>
  </body>

<script type="text/javascript" src="https://sandbox.web.squarecdn.com/v1/square.js"></script>
<script>
  const appId = '{{ $appId }}';
  const locationId = '{{ $locationId }}';

  async function initializeCard(payments) {
    const card = await payments.card();
    await card.attach('#card-container'); 
    return card; 
  }

  document.addEventListener('DOMContentLoaded', async function () {
    if (!window.Square) {
      throw new Error('Square.js failed to load properly');
    }

    const payments = window.Square.payments(appId, locationId);
    let card;
    try {
      card = await initializeCard(payments);
    } catch (e) {
      console.error('Initializing Card failed', e);
      return;
    }

    async function handlePaymentMethodSubmission(event, paymentMethod) {
      event.preventDefault();

      try {
        // disable the submit button as we await tokenization and make a
        // payment request.
        cardButton.disabled = true;
        const token = await tokenize(paymentMethod);
        const paymentResults = await createPayment(token);
        console.log(paymentResults);
        displayPaymentResults('SUCCESS');

        console.debug('Payment Success', paymentResults);
      } catch (e) {
        cardButton.disabled = false;
        displayPaymentResults('FAILURE');
        console.error(e.message);
      }
    }

    const cardButton = document.getElementById(
      'card-button'
    );
    cardButton.addEventListener('click', async function (event) {
      await handlePaymentMethodSubmission(event, card);
    });

  });

  // Call this function to send a payment token, buyer name, and other details
  // to the project server code so that a payment can be created with 
  // Payments API
  async function createPayment(token) {
    const body = JSON.stringify({
      _token: "{{ csrf_token() }}",
      locationId,
      sourceId: token,
    });
    const paymentResponse = await fetch('/cards', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body,
    });
    if (paymentResponse.ok) {
      return paymentResponse.json();
    }
    const errorBody = await paymentResponse.text();
    throw new Error(errorBody);
  }

  // This function tokenizes a payment method. 
  // The ‘error’ thrown from this async function denotes a failed tokenization,
  // which is due to buyer error (such as an expired card). It's up to the
  // developer to handle the error and provide the buyer the chance to fix
  // their mistakes.
  async function tokenize(paymentMethod) {
    const tokenResult = await paymentMethod.tokenize();
    if (tokenResult.status === 'OK') {
      return tokenResult.token;
    } else {
      let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
      if (tokenResult.errors) {
        errorMessage += ` and errors: ${JSON.stringify(
          tokenResult.errors
        )}`;
      }
      throw new Error(errorMessage);
    }
  }

  // Helper method for displaying the Payment Status on the screen.
  // status is either SUCCESS or FAILURE;
  function displayPaymentResults(status) {
    const statusContainer = document.getElementById(
      'payment-status-container'
    );
    if (status === 'SUCCESS') {
      statusContainer.classList.remove('is-failure');
      statusContainer.classList.add('is-success');
    } else {
      statusContainer.classList.remove('is-success');
      statusContainer.classList.add('is-failure');
    }

    statusContainer.style.visibility = 'visible';
  }    



  </script>
</html>
