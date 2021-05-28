# Magento2 Always Login As Customer

Magento 2.4 has a feature called 'Login as Customer', which gives Admin users the ability to start a session as that user. 
This module enables this feature, regardless of the 'Allow remote shopping assistance' Customer setting.

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
