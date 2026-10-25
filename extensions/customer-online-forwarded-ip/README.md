# Customers Online: Forwarded Client IP

Records the visitor's real IP address in **Reports → Customers Online** when the store runs behind a reverse proxy, load balancer or CDN.

| | |
|---|---|
| Type | OCMOD (1 operation) |
| Modifies | `catalog/controller/common/footer.php` |
| Database changes | None |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies and lints) |

## Why

Behind Nginx, HAProxy, an AWS load balancer or Cloudflare, `REMOTE_ADDR` is the proxy's address, so every online visitor shows the same IP. The proxy passes the original address in the `X-Forwarded-For` header.

## Behaviour

1. If `X-Forwarded-For` is present, take the **first** entry (the header can be `client, proxy1, proxy2`).
2. Use it only if it is a valid IPv4/IPv6 address (`filter_var(..., FILTER_VALIDATE_IP)`).
3. Otherwise fall back to the core behaviour (`REMOTE_ADDR`).

Only the *Customers Online* tracking in the footer is affected. Order IPs, fraud checks and other features keep using core logic.

## Security

`X-Forwarded-For` is an ordinary request header. **Install this only when a proxy you control sits in front of the store and overwrites or appends the header.** Without a proxy, a visitor can send any value and it will be recorded. If you use Cloudflare, consider their `CF-Connecting-IP` header or Nginx's `real_ip` module instead, which can restrict trusted proxy ranges.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/customer-online-forwarded-ip.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `customer-online-forwarded-ip.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original (v1.0)

- Takes the first address from a comma-separated chain (the original stored the whole header, e.g. `"1.2.3.4, 10.0.0.1"`).
- Validates the address; invalid values fall back to `REMOTE_ADDR`.
- Removed the `HTTP_CLIENT_IP` branch: it was only reached when `REMOTE_ADDR` was missing, and that header is equally client-controlled.
- Reads `$this->request->server` (OpenCart's sanitised copy) instead of `$_SERVER`.
