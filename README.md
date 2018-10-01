# HiPay Wallet cash-out library for Mirakl (advanced installation)

[![Build Status](https://hook.hipay.org/badge-ci/build/pi-ecommerce/hipay-wallet-cashout-mirakl-library/develop?service=github)](https://hook.hipay.org/badge-ci/build/pi-ecommerce/hipay-wallet-cashout-mirakl-library/develop?service=github)
[![Code Climate](https://codeclimate.com/github/hipay/hipay-wallet-cashout-mirakl-library/badges/gpa.svg)](https://codeclimate.com/github/hipay/hipay-wallet-cashout-mirakl-library) [![Package version](https://img.shields.io/packagist/v/hipay/hipay-wallet-cashout-mirakl-library.svg)](https://packagist.org/packages/hipay/hipay-wallet-cashout-mirakl-library) [![GitHub license](https://img.shields.io/badge/license-Apache%202-blue.svg)](https://raw.githubusercontent.com/hipay/hipay-wallet-cashout-mirakl-library/master/LICENSE.md)

The **HiPay Wallet cash-out library for Mirakl** is a PHP library which intends to facilitate cash-out operations between HiPay and the Mirakl marketplace solution.

## Important notice

Before getting started, be aware that this library needs to be integrated. **In most cases, you won't need to integrate it yourself** and will rather install the [standalone integration][repo-integration]. You may want to integrate this library yourself if you already have a back-end application and want to manage the cash-out workflow in it. 

**Unless you have specific needs, do not integrate this library**, but install the [standalone integration][repo-integration] which integrates it.

## Getting started

Read the **[project documentation][doc-home]** for comprehensive information about the requirements, general workflow and installation procedure.

## Resources
- [Full project documentation][doc-home] — To have a comprehensive understanding of the workflow and get the installation procedure
- [HiPay Support Center][hipay-help] — To get technical help from HiPay
- [Issues][project-issues] — To report issues, submit pull requests and get involved (see [Apache 2.0 License][project-license])
- [Change log][project-changelog] — To check the changes of the latest versions

## Features

- Creates HiPay Wallet accounts for your Mirakl merchants
- Retrieves your Mirakl payment operations
- Transfers funds from your technical HiPay Wallet account to your merchants' HiPay Wallet accounts
- Transfers operator's fees from your technical HiPay Wallet account to your operator's HiPay Wallet account
- Leverages the relevant HiPay Wallet API in order to execute withdrawals from HiPay Wallet to both the operator's and merchants' bank accounts

## License

The **HiPay Wallet cash-out library for Mirakl** is available under the **Apache 2.0 License**. Check out the [license file][project-license] for more information.

[doc-home]: https://developer.hipay.com/doc/hipay-marketplace-cashout-mirakl-library/

[hipay-help]: http://help.hipay.com

[project-issues]: https://github.com/hipay/hipay-wallet-cashout-mirakl-library/issues
[project-license]: LICENSE.md
[project-changelog]: CHANGELOG.md

[repo-integration]: https://github.com/hipay/hipay-wallet-cashout-mirakl-integration
