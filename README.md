# MatrixRate Postcode Priority Extension

A Magento 2 extension for the [WebShopApps MatrixRate](https://github.com/webshopapps/module-matrixrate) module that ensures more specific postcode patterns take priority over less specific ones.

## The Problem

When MatrixRate matches shipping rates using postcode patterns (via SQL `LIKE`), multiple patterns can match a single postcode. For example, postcode **NP10 1AA** matches `N%`, `NP%`, and `NP10%` simultaneously. Without this extension, all matching rates are returned, which can produce incorrect pricing.

This extension filters the results so that each shipping method uses only its **most specific** matching postcode pattern.

## Requirements

- PHP 8.1+
- Magento 2.4.4+
- [WebShopApps MatrixRate](https://github.com/webshopapps/module-matrixrate) 20.0.0+

## Installation

1. Copy the `WebShopApps` folder into your Magento `app/code/` directory:

```bash
cp -r WebShopApps/ /path/to/magento/app/code/
```

2. Enable and compile:

```bash
bin/magento module:enable WebShopApps_MatrixRatePostcodePriority
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## How It Works

The extension uses a Magento plugin (interceptor) on the MatrixRate resource model's `getRate()` method. After the original method returns all matching rates, the plugin:

1. Groups rates by shipping method
2. Within each group, calculates the specificity of each postcode pattern
3. Keeps only the most specific match per shipping method

This means the original MatrixRate module is **not modified** and can be updated independently.

## Documentation

See [WebShopApps/MatrixRatePostcodePriority/README.md](WebShopApps/MatrixRatePostcodePriority/README.md) for detailed documentation including specificity examples, troubleshooting, and configuration notes.

## License

Open Software License (OSL 3.0)
