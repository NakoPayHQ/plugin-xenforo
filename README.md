# NakoPay for XenForo

Accept Bitcoin payments on your XenForo forum through [NakoPay](https://nakopay.com).

- User upgrades and subscriptions via Bitcoin.
- Premium content gating and account upgrades.
- Stripe-style API: invoices created server-side, polled and webhook-notified.
- Signed webhooks (HMAC-SHA256, 5-minute replay window).
- Clean XenForo add-on architecture - no core modifications.

## Requirements

- XenForo 2.2+
- PHP 8.0+
- cURL extension enabled
- A NakoPay account (free) - <https://nakopay.com/dashboard/api-keys>

## Download

| # | Source | When to use |
|---|--------|-------------|
| 1 | **XenForo Resource Manager** - <https://xenforo.com/community/resources/> | *Listing pending review - use option 2 in the meantime.* |
| 2 | **GitHub Releases zip** - <https://github.com/NakoPayHQ/plugin-xenforo/releases/latest/download/nakopay-xenforo.zip> | Available today. Download `nakopay-xenforo.zip`. |
| 3 | **Build from source** | See bottom of this file. |

## Install

1. Download `nakopay-xenforo.zip` and unzip it.

2. Upload the `NakoPay/Payments` folder into your XenForo installation's `src/addons/` directory so the final path is:

   ```
   <xenforo-root>/src/addons/NakoPay/Payments/
   ```

   Use SFTP (FileZilla, WinSCP, Cyberduck) or your hosting panel's File Manager.

3. In XenForo admin go to **Add-ons -> Install/upgrade** and install **NakoPay Bitcoin Payments**.

   Or via CLI:
   ```bash
   php cmd.php xf:addon-install NakoPay/Payments
   ```

4. Go to **Setup -> Options -> NakoPay Bitcoin Payments** and configure:
   - **API Key** - `sk_live_...` (or `sk_test_...` for testing). Get one at <https://nakopay.com/dashboard/api-keys>.
   - **Webhook Signing Secret** - shown once when you create a webhook endpoint in your NakoPay dashboard.
   - **Currency** - your preferred fiat currency (e.g. USD, EUR, GBP).

5. In your NakoPay dashboard, **Settings -> Webhooks -> Add endpoint**, paste your webhook URL:
   `https://your-forum.example/index.php?nakopay/webhook`

   Subscribe to `invoice.paid`, `invoice.completed`, `invoice.expired`, `invoice.cancelled`. Save and copy the signing secret back into step 4.

## How it works

- A "Pay with Bitcoin" button links to `/nakopay/pay` with amount, description, and upgrade_id.
- The controller creates a NakoPay invoice and renders a checkout page with QR code + Bitcoin address + amount.
- The checkout page polls `/nakopay/poll?invoice_id=in_xxx` every 5s. When the invoice flips to `paid`, the user is redirected back.
- The webhook receiver verifies the signature and (optionally) activates the user's XenForo upgrade for premium access.

## Use cases

- **User upgrades** - sell premium membership tiers (pass `upgrade_id` in the payment form to auto-activate).
- **Donations** - accept Bitcoin donations on a dedicated page.
- **Premium content** - gate threads, forums, or resources behind a one-time Bitcoin payment.
- **Account perks** - sell custom titles, increased attachment limits, banner styling, etc.

## Test mode

Use a `sk_test_...` key. Test invoices accept BTC testnet sends - grab funds from any testnet faucet.

## Uninstall

1. XenForo admin -> **Add-ons -> NakoPay Bitcoin Payments -> Uninstall**.

   Or via CLI:
   ```bash
   php cmd.php xf:addon-uninstall NakoPay/Payments
   ```

## Files

| Path | Purpose |
|------|---------|
| `addon.json` | Add-on metadata. |
| `Setup.php` | Install/upgrade/uninstall lifecycle. |
| `ApiClient.php` | NakoPay API client + signature verification. |
| `Pub/Controller/Payment.php` | Customer checkout + polling. |
| `Pub/Controller/Webhook.php` | Webhook receiver. |
| `Admin/Controller/Options.php` | Admin settings controller. |
| `_output/templates/public/nakopay_checkout.html` | Customer checkout template. |
| `_output/templates/admin/nakopay_options.html` | Admin settings template. |

## Build from source

```bash
git clone https://github.com/NakoPayHQ/plugin-xenforo.git
cd plugin-xenforo
zip -r nakopay-xenforo.zip . -x "*.git*" "tests/*" "*.DS_Store"
```

## Support

- Issues: <https://github.com/NakoPayHQ/plugin-xenforo/issues>
- Email: support@nakopay.com

## About XenForo

[XenForo](https://xenforo.com/) - a compelling community forum platform with extensive customization. Visit their website to learn more.

## License

MIT.
