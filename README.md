# laravel-anaf

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pristavu/laravel-anaf.svg?style=flat-square)](https://packagist.org/packages/pristavu/laravel-anaf)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pristavu/laravel-anaf/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pristavu/laravel-anaf/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pristavu/laravel-anaf/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pristavu/laravel-anaf/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/pristavu/laravel-anaf.svg?style=flat-square)](https://packagist.org/packages/pristavu/laravel-anaf)

This package makes it easy to work with ANAF services in Laravel applications.

## Installation

You can install the package via composer:

```bash
composer require pristavu/laravel-anaf
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="anaf-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="anaf-config"
```

## What you can do with this package

- OAuth2 - authentication/authorization.
    - get authorization url
    - retrieve access token
    - refresh access token 
- eFactura - client/connector (Oauth2 token required) for interacting with the eFactura API.
    - retrieve messages/invoices (regular and paginated)
    - download invoices as zip
    - extract invoice xml, signature and invoice dto from zip
    - validate xml invoices
    - upload xml invoices (B2B, B2C)
    - convert xml invoices to PDF
    - check message status
- taxPayer - client/connector for interacting with the taxpayer API.
    - vat status check and other taxpayer information (by cif)
    - balance sheet retrieval (by year)

---

## OAuth2 usage

Add the following to your `.env` file:

```
ANAF_CLIENT_ID=your-client-id
ANAF_CLIENT_SECRET=your-client-secret
ANAF_REDIRECT_URI=http://your-callback-url/auth/anaf/callback
```

or update config/anaf.php with your environment variables

```php
[ 
  'oauth' => [
        'client_id' => env('ANAF_CLIENT_ID'),
        'client_secret' => env('ANAF_CLIENT_SECRET'),
        'redirect_uri' => env('ANAF_REDIRECT_URI', 'http://localhost/auth/anaf/callback'),
  ],
  ...
]

// you can pass the config values directly when initializing the oauth2 authenticator
$connector = Pristavu\Anaf\Facades\Anaf::oauth(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    redirectUri: 'http://your-callback-url/auth/anaf/callback'
);
```

### Redirect to authorization server

```php
// In redirect controller method eg: '/auth/anaf/redirect'

public function __invoke(): RedirectResponse
{    
    $connector = Pristavu\Anaf\Facades\Anaf::oauth();            
    $state = Str::random(32);    
    // store the state in a scoped session (L12) or cache for later validation
    session()->cache()->put('anaf_oauth_state', $state, now()->addMinutes(5));
    
    $authorizationUrl = $connector->getAuthorizationUrl(
        state: $state, 
        additionalQueryParameters: ['token_content_type' => 'jwt']
    );
    
    return redirect()->away($authorizationUrl);
}
```

### Token retrieval on auth callback

```php
// In callback controller method eg: '/auth/anaf/callback'

public function __invoke(Request $request): ?RedirectResponse
{
    $code = $request->get('code');
    $state = $request->get('state');
    $expectedState = session()->cache()->get('anaf_oauth_state');
    
    if($request->has('error')){
        abort(400, 'Error from authorization server: '.$request->get('error'));
    }
    
    if (!$code || !$state) {
        abort(400, 'Invalid state or code');
    }
      
    try {
        $connector = Pristavu\Anaf\Facades\Anaf::oauth(); 
        $authenticator = $connector->getAccessToken($code, $state, $expectedState);
    } catch (\Exception $e) {
        abort(400, 'Failed to get access token: ' . $e->getMessage());
    }
    
  
    // you can store the serialized token in session or cache for later use
    session()->cache()->put('anaf_oauth_authenticator', $authenticator->serialize());
       
    // or store the token in database or other persistent storage
    Pristavu\Anaf\Models\AccessToken::create([
      'user_id' => auth()->id(),
      'provider' => Provider::ANAF,
      'access_token' => $authenticator->getAccessToken(),
      'refresh_token' => $authenticator->getRefreshToken(),
      'expires_at' => $authenticator->getExpiresAt(),
    ]);
    
    return redirect('/home'); // or wherever you want to redirect the user
}
```

### Refreshing existing access token

```php
// initialize the oauth2 authenticator
$connector = \Pristavu\Anaf\Facades\Anaf::oauth();
 
// get serialized authenticator from session or cache
$serialized = session()->cache()->get('anaf_oauth_authenticator');
$authenticator = \Saloon\Http\Auth\AccessTokenAuthenticator::unserialize($serialized);

// or retrieve it from database access token model
$accessToken = Pristavu\Anaf\Models\AccessToken::query()->where('user_id', auth()->id())->first();
$authenticator = $accessToken->authenticator();

if ($authenticator->hasExpired()) {
    // We'll refresh the access token which will return a new authenticator   
    $authenticator = $connector->refreshAccessToken($authenticator);    
    
    // Store the new token serialized in session or cache
    session()->cache()->put('anaf_oauth_authenticator', $authenticator->serialize());
    
    // or update the existing token model in database
    $accessToken->update([
        'token' => $authenticator->getAccessToken(),
        'refresh_token' => $authenticator->getRefreshToken(),
        'expires_at' => $authenticator->getExpiresAt(),
    ]);
}
```

---

## Efactura usage (Oauth2 required)

### Initializing the client

```php
// You need a valid access token to initialize the efactura connector

// retrieve access token from database or other storage
$accessToken = Pristavu\Anaf\Models\AccessToken::query()->where('user_id', auth()->id())->first()->access_token;

// initialize the efactura connector / client
$connector = Pristavu\Anaf\Facades\Anaf::eFactura(accessToken: $accessToken);
```

### Switching to test mode (sandbox)

```php
// Live (production) endpoint is used by default and is forced for certain operations eg: validateInvoice, convertInvoice
// Test mode can be used only for uploading, messages ,messagesPaginated, uploadInvoice, messageStatus, downloadInvoice
$connector->inTestMode();
```

### Debugging Request & Response

```php
// enable logging of requests and responses
// die will stop execution after logging the response
$connector->debug();  // $connector->debug(die: true); 

// Separate Debuggers
$connector->debugRequest(); // connector->debugRequest(die: true);
$connector->debugResponse(); // connector->debugResponse(die: true);

```

### Caching

- For certain operations like downloading invoices, cache is enabled by default to avoid hitting ANAF download limit rate (10 downloads/day for same $downloadId).

```php
// If you want to disable caching for all operations you can do it like this:
$connector->disableCaching()->downloadInvoice(downloadId: $downloadId);
// or for invalidating cached content before downloading again:
$connector->invalidateCache()->downloadInvoice(downloadId: $downloadId);
```

### Retrieving messages/invoices

- You need to use paginated messages if you expect more than 500 messages/invoices for specified period.

```php
// days - number of days between 1 and 60
// type can be one of: MessageType::{SENT/RECEIVED/ERROR/MESSAGE} -  if none provided, all types are retrieved

// eg: retrieve sent messages/invoices for cif 123456 from last 60 days
$response = $connector->messages(cif: 123456, days: 60, type: MessageType::SENT); // returns a MessagesResponse 

if($response->success){
    $response->messages->each(function(Message $message){
        // do something with $message
        $message->cif; // the cif associated with the message/invoice
        $message->upload_id; // the upload id for the message/invoice
        $message->download_id; // the download id for the message/invoice
        $message->type; // the type of message/invoice    
        $message->created_at; // Carbon instance of creation date
        $message->description; // description/details of the message/invoice
    });
} else {
    // handle error
    $response->error;

// eg: retrieve any type of messages/invoices for cif 123456 from last 10 days
$response = $connector->messages(cif: 123456, days: 10);

```

### Retrieving paginated messages/invoices
- Somehow even if the paginated response should provide 500 messages per page and total messages are less than 500,
messages are divided into two pages (eg: total 95 messages are returned as 2 pages, first with 49 and second with 46 messages).

```php
// period - interval must not exceed 60 days
// type can be one of: MessageType::{SENT/RECEIVED/ERROR/MESSAGE} -  if none provided, all types are retrieved

// retrieve sent messages/invoices for cif 123456 from last 60 days (paginated page 1)
$period = \Carbon\CarbonPeriod::create(now()->subDays(60), now());
$response = $connector->messagesPaginated(cif: 123456, period: $period, page: 1, type: MessageType::SENT); // returns a PaginatedMessagesResponse

if($response->success){
    $response->messages->each(function(Message $message){
        // do something with $message
        $message->cif; // the cif associated with the message/invoice
        $message->upload_id; // the upload id for the message/invoice
        $message->download_id; // the download id for the message/invoice
        $message->type; // the type of message/invoice    
        $message->created_at; // Carbon instance of creation date
        $message->description; // description/details of the message/invoice
    });
    
    // paginated response metadata
    $response->meta->total; // number of messages in selected period
    $response->meta->per_page; // messages per page (default 500)
    $response->meta->current_page; // current page number
    $response->meta->last_page; // last page number
} else {
    // handle error
    $response->error;
}


// or using the toPeriod method
// retrieve any messages/invoices  for cif 123456 from last 10 days (paginated page 2)
$period = now()->subDays(10)->toPeriod(now());
$response = $connector->messagesPaginated(cif: 123456, period: $period, page: 2);
```

### Downloading messages/invoices

```php
$downloadId = 987654321; // the download_id of the message/invoice
$response = $connector->downloadInvoice(downloadId: $downloadId);

if($response->success){
    // save the zip content to a file
    Storage::disk('private')->put("/invoices/{$downloadId}.zip",$response->content);
    
    // optionally you can extract files from the zip message/invoice using the Extract helper without saving archive to disk    
    $message = Pristavu\Anaf\Support\Extract::from($response->content);
    
    // get xml invoice, signature and dto invoice objects
    $message->xmlInvoice();
    $message->signature();
    // dto invoice will be null if unzipping a non invoice message (eg: xml response error message)
    $message->dtoInvoice();       
}
else {
    // handle error
    $response->error; // array of download errors
}

```

### Validating messages/invoices

```php
$xml = Storage::disk('private')->get('invoices/12345/987654321.xml'); 
// optionally you can pass the full path to xml
$xml = Storage::disk('private')->path('invoices/12345/987654321.xml');

$response = $connector->validateInvoice(
    xml: $xml,
    standard: \Pristavu\Anaf\Enums\DocumentStandard::FCN, // optional, default is FACT1  
);

if($response->success){
   // do something with $response 
} else {
   // handle errors
   $response->errors; // array of validation errors
}

```

### Uploading an invoice

```php
$xml = Storage::disk('private')->get('invoices/12345/987654321.xml');
// optionally you can pass the full path to xml
$xml = Storage::disk('private')->path('invoices/12345/987654321.xml');
$response = $connector->uploadInvoice(
    cif: 123456,
    xml: $xml,
    standard: \Pristavu\Anaf\Enums\XmlStandard::UBL, // optional, default is UBL
    isExternal:  false, // optional, default is false
    isSelfInvoice: false, // optional, default is false
    isLegalEnforcement: false // optional, default is false
);

if($response->success){
    // do something with $response
    $response->upload_id; // the upload id of the invoice
    
}
else {
    // handle error
    $response->error; 
}
```

### Converting invoice to PDF

```php
$xml = Storage::disk('private')->get('invoices/12345/987654321.xml'); 
// optionally you can pass the full path to xml
$xml = Storage::disk('private')->path('invoices/12345/987654321.xml');
$response = $connector->convertInvoice(xml: $xml, standard: DocumentStandard::FACT1, withoutValidation: true);

if($response->success){
    // save the pdf content to a file
    Storage::disk('private')->put("/invoices/12345/987654321.pdf",$response->content);
}
```

### Message status

```php
$uploadId = 987654321; // the message id to check status for
$response = $connector->messageStatus(uploadId: $uploadId);

if($response->success){
   // do something with $response
   $response->status
   $response->download_id; // download id if available
}
else {
   // handle error
   $response->error;
}
```

## Testing

```bash
composer test
```

### Using the fake client

You can use the mock client to simulate API responses during testing in your laravel application.

```php
use Saloon\Http\Faking\MockClient;
use Requests\Efactura\MessagesRequest;

test('my test', function () {
    // arrange
    $mockClient = new MockClient([
        MessagesRequest::class => MockResponse::make(
        body: [
            'mesaje' => [
                [
                    'data_creare' => 202508291153,
                    'cif' => 123456,
                    'id_solicitare' => 999999999,
                    'detalii' => 'Factura cu id_incarcare=999999999 emisa de cif_emitent=123456 pentru cif_beneficiar=987654',
                    'tip' => 'FACTURA TRIMISA',
                    'id' => 888888888,
                ],
                ...              
            ]
        ],
        status: 200
        ),
    ]);
    
    // act
    $connector = Anaf::eFactura(accessToken: 'TEST_TOKEN');
    $messages = $connector->withMockClient($mockClient)->messages(cif: 123456, days: 60);
    
    // assert
    expect($messages)->toBeArray();    
});
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Andrei Pristavu](https://github.com/pristavu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
