# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| Current `main` (see CHANGELOG) | Yes |
| Original 2021 versions (first commit) | **No.** They contain the SQL injection and other issues fixed in this release. Do not install them. |

## Reporting a vulnerability

Please do not open a public issue. Use GitHub's **Report a vulnerability** (Security → Advisories) on this repository, with:

- the extension and version,
- OpenCart and PHP versions,
- steps to reproduce and the impact.

You will get an acknowledgement within 7 days.

## Scope notes

- These extensions modify OpenCart core files at runtime (OCMOD). Vulnerabilities in OpenCart itself should be reported to the [OpenCart project](https://github.com/opencart/opencart/security).
- `customer-online-forwarded-ip` trusts the `X-Forwarded-For` header by design. Install it only behind a proxy you control (see its README).
