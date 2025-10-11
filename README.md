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

# What you can do with this package

### For now the package provides two main features:
- OAuth2 - authentication/authorization.
- Efactura - client/connector.
  - Retrieve messages/invoices (regular and paginated)
  - Download messages/invoices as zip
  - Validate messages/invoices
  - Upload messages/invoices as xml
  - Convert messages/invoices to PDF
  - Check message status

---

# OAuth2 usage

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

# Efactura usage

### Initializing the client

```php
// retrieve access token from database or other storage
$accessToken = Pristavu\Anaf\Models\AccessToken::query()->where('user_id', auth()->id())->first()->access_token;

// initialize the efactura connector / client
$connector = Pristavu\Anaf\Facades\Anaf::efactura(accessToken: $accessToken);
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

### Retrieving messages/invoices

- You need to use paginated messages if you expect more than 500 messages/invoices for specified period.

```php
// days - number of days between 1 and 60
// type can be one of: MessageType::{SENT/RECEIVED/ERROR/MESSAGE} -  if none provided, all types are retrieved

//  sent messages/invoices for cif 123456 from last 60 days
$response = $connector->messages(cif: 123456, days: 60, type: MessageType::SENT);

// retrieve any type of messages for cif 123456 from last 10 days
$response = $connector->messages(cif: 123456, days: 10);

```

### Retrieving paginated messages/invoices

```php
// period - interval must not exceed 60 days
// type can be one of: MessageType::{SENT/RECEIVED/ERROR/MESSAGE} -  if none provided, all types are retrieved

// retrieve sent messages/invoices for cif 123456 from last 60 days (paginated page 1)
$period = \Carbon\CarbonPeriod::create(now()->subDays(60), now());
$response = $connector->messagesPaginated(cif: 123456, period: $period, page: 1, type: MessageType::SENT);

// or using the toPeriod method
// retrieve any messages/invoices  for cif 123456 from last 10 days (paginated page 2)
$period = now()->subDays(10)->toPeriod(now());
$response = $connector->messagesPaginated(cif: 123456, period: $period, page: 2);
```

### Downloading messages/invoices

```php
$downloadId = 987654321; // the download_id of the message/invoice
$response = $connector->downloadInvoice(downloadId: $downloadId);

if($response['success']){
    // save the zip content to a file
    Storage::disk('private')->put("/invoices/{$downloadId}.zip",$response['content']);
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

if($response['is_valid']){
   // upload the invoice
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

if($response['success']){
    // do something with $response['upload_id']   
}
```

### Converting invoice to PDF

```php
$xml = Storage::disk('private')->get('invoices/12345/987654321.xml'); 
// optionally you can pass the full path to xml
$xml = Storage::disk('private')->path('invoices/12345/987654321.xml');
$response = $connector->convertInvoice(xml: $xml, standard: DocumentStandard::FACT1, withoutValidation: true);

if($response['success']){
    // save the pdf content to a file
    Storage::disk('private')->put("/invoices/12345/987654321.pdf",$response['content']);
}
```

### Message status

```php
$uploadId = 987654321; // the message id to check status for
$response = $connector->messageStatus(uploadId: $uploadId);

if($response['success']){
   // do something with $response['status']  
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
    $efactura = Anaf::efactura(accessToken:  'TEST_TOKEN');
    $messages = $efactura->withMockClient($mockClient)->messages(cif: 123456, days: 60);
    
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
