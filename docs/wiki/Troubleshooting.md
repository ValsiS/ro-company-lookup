# Troubleshooting

## Connection issues

- Ensure outbound HTTPS access to `webservicesp.anaf.ro`.
- Increase `anaf.timeout` and `anaf.connect_timeout` for slow networks.

## Missing fields

ANAF does not always return every field. The package maps defensively and may return nulls for optional values.

## Inspect raw payload

Set `enable_raw` to true or use the `--raw` flag on the command to include `meta.raw`.
