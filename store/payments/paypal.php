<?php
/**
 * payments/paypal.php
 *
 * PayPal REST API v2 integration for the portfolio store.
 *
 * Implements the PayPal Orders API flow:
 *   1. Obtain an OAuth 2.0 access token via client credentials.
 *   2. Create a PayPal Order and receive an approval URL.
 *   3. Capture the approved order after the customer returns.
 *
 * Both sandbox and live modes are supported; the active mode is controlled
 * by the PAYPAL_MODE constant in config.php.
 *
 * Usage
 * -----
 *   require_once ROOT_PATH . '/payments/paypal.php';
 *
 *   // Step 1 – Create order and get redirect URL
 *   $result = paypalCreateOrder($items, $total, $returnUrl, $cancelUrl);
 *   redirect($result['approve_url']);
 *
 *   // Step 2 – Capture after customer returns (success.php)
 *   $capture = paypalCaptureOrder($paypalOrderId);
 *   if ($capture['status'] === 'COMPLETED') { ... }
 *
 * Architecture note
 * -----------------
 * Only PayPal-specific code lives in this file. checkout.php and success.php
 * call the functions here through a thin wrapper in functions.php. To add a
 * second payment provider (e.g. Stripe), create payments/stripe.php with the
 * same public contract and add a routing switch in checkout.php.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

/**
 * Retrieves a short-lived OAuth 2.0 access token from the PayPal API.
 *
 * Uses HTTP Basic authentication with client credentials. The token is
 * cached in a static variable for the lifetime of the current request.
 *
 * @return string  Bearer access token.
 * @throws RuntimeException  If the token request fails.
 */
function paypalGetAccessToken(): string
{
    static $cachedToken = null;
    if ($cachedToken !== null) {
        return $cachedToken;
    }

    $url  = PAYPAL_API_BASE . '/v1/oauth2/token';
    $auth = base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        throw new RuntimeException('PayPal token request cURL error: ' . $curlErr);
    }

    $data = json_decode((string) $response, true);

    if ($httpCode !== 200 || empty($data['access_token'])) {
        throw new RuntimeException(
            'PayPal token request failed (HTTP ' . $httpCode . '): ' .
            ($data['error_description'] ?? $response)
        );
    }

    $cachedToken = $data['access_token'];
    return $cachedToken;
}

/**
 * Sends a JSON-encoded request to the PayPal API.
 *
 * @param string               $method   HTTP method ('GET', 'POST', 'PATCH').
 * @param string               $endpoint API path, e.g. '/v2/checkout/orders'.
 * @param array<string, mixed> $body     Request body (will be JSON-encoded).
 *
 * @return array{http_code: int, data: array<string, mixed>}
 * @throws RuntimeException  On network failure.
 */
function paypalRequest(string $method, string $endpoint, array $body = []): array
{
    $token = paypalGetAccessToken();
    $url   = PAYPAL_API_BASE . $endpoint;

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
        'Prefer: return=representation',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        throw new RuntimeException('PayPal API cURL error: ' . $curlErr);
    }

    $data = json_decode((string) $response, true) ?? [];

    return ['http_code' => $httpCode, 'data' => $data];
}

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------

/**
 * Creates a PayPal Order and returns the approval URL to redirect the
 * customer to PayPal's checkout page.
 *
 * The order is created with the CAPTURE intent, meaning the payment is
 * captured immediately when the customer approves it.
 *
 * @param array<int, array<string, mixed>> $items      Enriched cart items (from getCartItems()).
 * @param float                            $total      Order grand total (server-side calculated).
 * @param string                           $returnUrl  URL PayPal redirects to on success.
 * @param string                           $cancelUrl  URL PayPal redirects to on cancellation.
 *
 * @return array{paypal_order_id: string, approve_url: string}
 * @throws RuntimeException  If PayPal order creation fails.
 */
function paypalCreateOrder(array $items, float $total, string $returnUrl, string $cancelUrl): array
{
    // Build item detail lines for the PayPal order (for transparency on PayPal's UI)
    $itemList    = [];
    $itemsTotal  = 0.0;

    foreach ($items as $item) {
        $unitAmount  = round((float) $item['price'], 2);
        $itemsTotal += $unitAmount * (int) $item['qty'];

        $itemList[] = [
            'name'        => mb_substr($item['name'], 0, 127),
            'unit_amount' => [
                'currency_code' => CURRENCY,
                'value'         => number_format($unitAmount, 2, '.', ''),
            ],
            'quantity'    => (string) (int) $item['qty'],
        ];
    }

    $itemsTotal = round($itemsTotal, 2);
    $grandTotal = round($total, 2);

    $payload = [
        'intent'          => 'CAPTURE',
        'purchase_units'  => [[
            'amount' => [
                'currency_code' => CURRENCY,
                'value'         => number_format($grandTotal, 2, '.', ''),
                'breakdown'     => [
                    'item_total' => [
                        'currency_code' => CURRENCY,
                        'value'         => number_format($itemsTotal, 2, '.', ''),
                    ],
                ],
            ],
            'items' => $itemList,
        ]],
        'application_context' => [
            'brand_name'          => SITE_NAME,
            'landing_page'        => 'BILLING',
            'user_action'         => 'PAY_NOW',
            'return_url'          => $returnUrl,
            'cancel_url'          => $cancelUrl,
        ],
    ];

    $result = paypalRequest('POST', '/v2/checkout/orders', $payload);

    if ($result['http_code'] !== 201) {
        throw new RuntimeException(
            'Failed to create PayPal order (HTTP ' . $result['http_code'] . '): ' .
            json_encode($result['data'])
        );
    }

    $paypalOrderId = $result['data']['id'] ?? '';
    $approveUrl    = '';

    foreach ($result['data']['links'] ?? [] as $link) {
        if ($link['rel'] === 'approve') {
            $approveUrl = $link['href'];
            break;
        }
    }

    if ($paypalOrderId === '' || $approveUrl === '') {
        throw new RuntimeException('PayPal order response missing ID or approve link.');
    }

    return [
        'paypal_order_id' => $paypalOrderId,
        'approve_url'     => $approveUrl,
    ];
}

/**
 * Captures an approved PayPal Order and returns the capture details.
 *
 * Must be called server-side after the customer returns from PayPal with
 * the `token` query parameter. Verifies and finalises the payment.
 *
 * @param string $paypalOrderId  The PayPal order ID (from the `token` query param).
 *
 * @return array<string, mixed>  Full capture response from the PayPal API.
 * @throws RuntimeException  If the capture request fails.
 */
function paypalCaptureOrder(string $paypalOrderId): array
{
    if ($paypalOrderId === '') {
        throw new RuntimeException('paypalCaptureOrder: empty PayPal order ID supplied.');
    }

    $endpoint = '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture';
    $result   = paypalRequest('POST', $endpoint);

    if (!in_array($result['http_code'], [200, 201], true)) {
        throw new RuntimeException(
            'Failed to capture PayPal order (HTTP ' . $result['http_code'] . '): ' .
            json_encode($result['data'])
        );
    }

    return $result['data'];
}

/**
 * Checks whether a PayPal capture response indicates a completed payment.
 *
 * Inspects the top-level `status` field and verifies that at least one
 * purchase unit has a COMPLETED capture, providing defence-in-depth against
 * spoofed responses.
 *
 * @param array<string, mixed> $captureData  Response from paypalCaptureOrder().
 *
 * @return bool  TRUE if the payment is fully captured.
 */
function paypalIsPaymentComplete(array $captureData): bool
{
    if (($captureData['status'] ?? '') !== 'COMPLETED') {
        return false;
    }

    // Secondary check: verify at least one capture unit is COMPLETED
    foreach ($captureData['purchase_units'] ?? [] as $unit) {
        foreach ($unit['payments']['captures'] ?? [] as $capture) {
            if (($capture['status'] ?? '') === 'COMPLETED') {
                return true;
            }
        }
    }

    return false;
}

/**
 * Extracts the PayPal transaction/capture ID from a capture response.
 *
 * @param array<string, mixed> $captureData  Response from paypalCaptureOrder().
 *
 * @return string  Transaction ID, or empty string if not found.
 */
function paypalGetTransactionId(array $captureData): string
{
    foreach ($captureData['purchase_units'] ?? [] as $unit) {
        foreach ($unit['payments']['captures'] ?? [] as $capture) {
            if (!empty($capture['id'])) {
                return $capture['id'];
            }
        }
    }
    return '';
}
