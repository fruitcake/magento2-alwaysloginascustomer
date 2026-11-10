# Magento2 Always Login As Customer

[![CI](https://github.com/fruitcake/magento2-alwaysloginascustomer/actions/workflows/ci.yml/badge.svg)](https://github.com/fruitcake/magento2-alwaysloginascustomer/actions/workflows/ci.yml)

Magento 2.4 has a feature called 'Login as Customer', which gives Admin users the ability to start a session as that user.
This module enables this feature, regardless of the 'Allow remote shopping assistance' Customer setting.

## Requirements

- Magento 2.4 or later (Login as Customer was added in 2.4.0)
- PHP 7.3 or later

## Install

```
composer require fruitcake/magento2-alwaysloginascustomer
php bin/magento module:enable Fruitcake_AlwaysLoginAsCustomer
php bin/magento setup:upgrade
```

## Usage

By default, when the customer hasn't given permissions you will get this warning:

> The user has not enabled the "Allow remote shopping assistance" functionality. Contact the customer to discuss this user configuration.

This module ignores the setting and will allow Login as Customer, regardless of the configuration.

**NOTE: It is up to you to explain this to the customer and make sure admins follow the correct protocols**

What the module changes:

- Login as Customer is allowed for every customer, whatever their "Allow remote shopping assistance" setting.
- The "Allow remote shopping assistance" checkbox is hidden on the storefront registration and account edit forms
  (Luma and Hyvä). The customer's stored preference is left as it is.
- A "Login as customer" button is added to the admin customer grid. When "Store View To Login In" is set to
  manual selection, the popup lets the admin pick the store; otherwise the customer's own store is used.
  The button is only shown when Login as Customer is enabled and the admin has the `Magento_LoginAsCustomer::login` permission.

## Development

Dependencies come from the [Mage-OS mirror](https://mirror.mage-os.org/), so no repo.magento.com keys are needed.

```
composer install
composer test   # PHPUnit unit tests
composer cs     # Magento2 coding standard
```
