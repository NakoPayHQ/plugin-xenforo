# Changelog
## 0.2.0 - 2026-05-17

### Changed
- Default API base URL is now https://api.nakopay.com/v1/ (branded primary). Added BASE_FALLBACK constant pointing at Supabase functions URL.

## 0.1.0 - 2026-05-08

- Initial release.
- Full payment flow: Pay with Bitcoin button -> NakoPay invoice -> QR + address checkout -> 5s status polling -> automatic redirect on paid.
- Signed webhook receiver (X-NakoPay-Signature, HMAC-SHA256, 5-minute replay window).
- Admin options page (API key, webhook secret, currency).
- Local xf_nakopay_orders table for idempotency and reuse of in-flight orders.
- Optional user upgrade activation on successful payment.
