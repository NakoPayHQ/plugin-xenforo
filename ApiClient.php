<?php
/**
 * NakoPay API client for XenForo.
 *
 * @package NakoPay/BtcPay
 */

namespace NakoPay\BtcPay;

class ApiClient
{
    const VERSION       = '0.1.0';
    const BASE_URL      = 'https://daslrxpkbkqrbnjwouiq.supabase.co/functions/v1/';
    const SIG_TOLERANCE = 300;

    public function getApiKey(): string
    {
        $key = trim((string) \XF::options()->nakoPayApiKey);
        if ($key === '') {
            throw new \RuntimeException('NakoPay API key is not configured.');
        }
        return $key;
    }

    public function getWebhookSecret(): string
    {
        return trim((string) \XF::options()->nakoPayWebhookSecret);
    }

    public function getCurrency(): string
    {
        return strtoupper(trim((string) \XF::options()->nakoPayCurrency)) ?: 'USD';
    }

    /* ----------------------------------------------------------------- HTTP */

    public function request(string $method, string $path, ?array $body = null): array
    {
        $url = self::BASE_URL . ltrim($path, '/');
        $ch  = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Accept: application/json',
            'User-Agent: NakoPay-XenForo/' . self::VERSION,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body !== null ? json_encode($body) : null,
        ]);
        $raw    = curl_exec($ch);
        $err    = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['_ok' => false, '_status' => 0, '_error' => $err ?: 'network error'];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['_ok' => false, '_status' => $status, '_error' => 'invalid json', '_raw' => $raw];
        }
        $decoded['_ok']     = $status >= 200 && $status < 300;
        $decoded['_status'] = $status;
        return $decoded;
    }

    public function createInvoice(array $args): array
    {
        return $this->request('POST', 'invoices-create', [
            'amount'         => (string) $args['amount'],
            'currency'       => strtoupper((string) ($args['currency'] ?? $this->getCurrency())),
            'coin'           => strtoupper((string) ($args['coin'] ?? 'BTC')),
            'description'    => (string) ($args['description'] ?? 'XenForo payment'),
            'customer_email' => (string) ($args['customer_email'] ?? ''),
            'metadata'       => array_filter([
                'xf_user_id'    => $args['xf_user_id'] ?? null,
                'xf_upgrade_id' => $args['xf_upgrade_id'] ?? null,
                'xf_item_id'    => $args['xf_item_id'] ?? null,
                'source'        => 'xenforo',
            ], fn($v) => $v !== null && $v !== ''),
        ]);
    }

    public function getInvoice(string $id): array
    {
        return $this->request('GET', 'invoices-get?id=' . rawurlencode($id));
    }

    /* ----------------------------------------------------------- webhook sig */

    public function verifyWebhook(string $rawBody, string $sigHeader, ?string $secretOverride = null): bool
    {
        $secret = $secretOverride ?? $this->getWebhookSecret();
        if ($secret === '' || $sigHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sigHeader) as $kv) {
            $kv = trim($kv);
            if ($kv === '' || strpos($kv, '=') === false) continue;
            [$k, $v] = explode('=', $kv, 2);
            $parts[trim($k)] = trim($v);
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $t = (int) $parts['t'];
        if (abs(time() - $t) > self::SIG_TOLERANCE) {
            return false;
        }

        $expected = hash_hmac('sha256', $t . '.' . $rawBody, $secret);
        return hash_equals($expected, $parts['v1']);
    }
}
