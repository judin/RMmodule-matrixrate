# MatrixRate Postcode Priority Extension

A Magento 2 extension that ensures more specific postcode patterns take priority over less specific ones when using the WebShopApps MatrixRate module.

## The Problem

When using MatrixRate with UK postcodes, you might have rates configured like:

| Postcode Pattern | Shipping Method | Price  |
|------------------|-----------------|--------|
| `*`              | Standard        | £10.00 |
| `N%`             | Standard        | £5.00  |
| `NP%`            | Standard        | £7.50  |
| `NW%`            | Standard        | £4.00  |

When a customer enters postcode "NP10 1AA", multiple patterns match:
- `*` matches (matches everything)
- `N%` matches (starts with N)
- `NP%` matches (starts with NP)

Without this extension, the customer might see multiple shipping options or get the wrong price.

## The Solution

This extension filters the results so that only the **most specific matching pattern** is used for each shipping method.

### Examples

**Customer postcode: NW1 1AA (North West London)**
- Matches: `*`, `N%`, `NW%`
- Result: Uses `NW%` at £4.00

**Customer postcode: NP10 1AA (Newport)**
- Matches: `*`, `N%`, `NP%`
- Result: Uses `NP%` at £7.50

**Customer postcode: N1 1AA (North London)**
- Matches: `*`, `N%`
- Result: Uses `N%` at £5.00

**Customer postcode: SW1 1AA (South West London)**
- Matches: `*`
- Result: Uses `*` at £10.00

### Specificity Ranking

| Pattern    | Specificity | Description |
|------------|-------------|-------------|
| `*`        | 0           | Matches everything (lowest priority) |
| `N%`       | 1           | One specific character |
| `NP%`      | 2           | Two specific characters |
| `NW%`      | 2           | Two specific characters |
| `NP10%`    | 4           | Four specific characters |
| `NP10 1AA` | 108         | Exact match (highest priority) |

## Requirements

- PHP 8.1 or higher
- Magento 2.4.4 or higher
- WebShopApps MatrixRate 20.0.0 or higher

## Installation

1. Copy the `MatrixRatePostcodePriority` folder to `app/code/WebShopApps/MatrixRatePostcodePriority/`

2. Enable the module:
```bash
bin/magento module:enable WebShopApps_MatrixRatePostcodePriority
bin/magento setup:upgrade
bin/magento cache:clean
```

## How It Works

The extension uses a Magento plugin (interceptor) on the `getRate()` method of the MatrixRate resource model. After the original method returns matching rates, the plugin re-sorts them by postcode specificity.

This approach means:
- The original MatrixRate module is **not modified**
- You can update MatrixRate independently
- The extension can be easily disabled if needed

## Disabling

To disable without uninstalling:

```bash
bin/magento module:disable WebShopApps_MatrixRatePostcodePriority
bin/magento cache:clean
```

## License

Open Software License (OSL 3.0)
