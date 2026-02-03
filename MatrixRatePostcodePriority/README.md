# MatrixRate Postcode Priority Extension

A Magento 2 extension that ensures more specific postcode patterns take priority over less specific ones when using the WebShopApps MatrixRate module.

## The Problem

When using MatrixRate with UK postcodes, you might have rates configured like:

| Postcode Pattern | Price |
|------------------|-------|
| N%               | £5.00 |
| NP%              | £7.50 |

When a customer enters postcode "NP10 1AA", both patterns match:
- `N%` matches (starts with N)
- `NP%` matches (starts with NP)

Without this extension, the less specific `N%` rate might be returned instead of the more appropriate `NP%` rate.

## The Solution

This extension intercepts the rate lookup and sorts results by **postcode specificity**, ensuring the most specific matching pattern is always used first.

### Specificity Examples

| Pattern    | Specificity | Description |
|------------|-------------|-------------|
| `*`        | 0           | Matches everything |
| `N%`       | 1           | One specific character |
| `NP%`      | 2           | Two specific characters |
| `NP10%`    | 4           | Four specific characters |
| `NP10 1%`  | 6           | Six specific characters |
| `NP10 1AA` | 108         | Exact match (highest priority) |

## Requirements

- PHP 8.1 or higher
- Magento 2.4.4 or higher
- WebShopApps MatrixRate 20.0.0 or higher

## Installation

### Via Composer

```bash
composer require webshopapps/module-matrixrate-postcode-priority
bin/magento module:enable WebShopApps_MatrixRatePostcodePriority
bin/magento setup:upgrade
bin/magento cache:clean
```

### Manual Installation

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
