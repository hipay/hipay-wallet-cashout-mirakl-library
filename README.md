HiPay-Mirakl Cashout Connector
==============================

This library is made to ease a crucial step in a marketplace flow : the cashout towards the seller and the operator.
It is tailored for two specific marketplace actors : HiPay for the payments and money related handling and Mirakl as a seller management system.

---

# Prerequisite and recommendations

## General

- The library alone is useless without an integration. Please refer to the previous part to use the library without the need to code an application around it.
- The integrator must have knowledge of basic POO and PHP to continue this guide and the integration of the library. He (or she) should also know some common design patterns.
- Although this guide will try to be as exhaustive as possible, do not hesitate to dig through the classes to have a better understanding of the inner mechanics. I tried to put meaningful comments throughout the code.
- If you think that the documentation is not enough, do not hesitate to see the code of the integration.
- Do not hesitate to run the unit tests to ensure that everything is running smoothly. Please see the section [Test](#Test) for more information.


## System
- You must have at least PHP 5.3.9. The library has dependencies who need PHP at least at this version.
- You should have and use composer. It is the best way to install the library or the integration.

## HiPay
- You must have a technical account before starting. This is the wallet receiving all the money coming from the customer orders and subsequent payments.
- You must create the operator wallet beforehand. You need a email who will not be used for another  shop of the marketplace. For this you will need assistance from HiPay.
- The operator and technical accounts (wallets) don't need to be registered in the database.
- A good understanding or at least the Cashout API guide nearby may be useful.

## Mirakl
- Even though Mirakl do not enforce the filling of the phone number, it must be completed to create a HiPay Wallet.
- Even though Mirakl only enforce the filling of the IBAN and the BIC in the banking information section, all form fields must be completed to add the banking information to HiPay.
- You should use only alphanumeric characters for the fields especially for banking information. This is an HiPay limitation as they only accept this category of characters.
- The version of the API should be at least 3. I cannot warrant the behavior for an earlier version.
- Should the API version be higher, as long as the concerned API called remain retro compatible, there won't be a problem.
- There is no renaming of the documents therefore you should enfoin the sellers to name correctly the files before uploading them to Mirakl
# Installation

## Composer
The installation is best done with composer. Simply issue in the terminal, where the composer.json is :

    $ composer require hipay/mirakl-connector

For information, the git repository is currently located at https://github.com/hipay/hipay-wallet-cashout-mirakl.

## Manual
You can also directly extract an archive containing the code file where you want it extracted. However, you will need in this case to handle the auto loading of the classes yourself.

# Usage

## Processor
The main functionalities of the library are contained in the __process__ method the the classes extending the `Common/AbstractApiProcessor` class. Although the abstract class don't enforce the implementation of the process method, its all child class have one. This is the method you should call in your specific application to process the data.  

Therefore, most of the work of the integrator will be to create concrete implementation of the interfaces present in the library, and then construct the processors thanks to the previous objects and finally call the __process__ method.

### Interfaces implementation

#### Entity interfaces
These interfaces represent entities used in the code.

###### VendorInterface
The `VendorInterface` represent a vendor or an operator. More generally, it could be said that the `VendorInterface` represent an entity who will either send or receive money through HiPay. The six methods it imposes to implement, paired by two getter and setter, indicate the three properties the concrete class should have :

|Property Name|Description|
|-------------|-----------|
|`miraklId`|The shop identifier present in the view of the Mirakl shop.|
|`hipayId`|The id of the HiPay wallet.|
|`email`|The email use to create the HiPay Wallet. As the only available email is the one in the contact information section of the shop parameters, it is the one who is used.|

All of these should be unique within the storage of your choice. After creation, the properties should not change after their initial filling.

###### OperationInterface
The `OperationInterface` represent and operation to be executed, transfer and withdrawal, and its current state. Like before the the methods point out at the properties the concrete class should have :

|Property name|Description
|-------------|-----------|
|`miraklId`|The shop identifier present in the view of the Mirakl shop.
|`hipayId`|The id of the HiPay wallet.
|`withdrawId`|The withdraw id. Will be completed after the withdrawal step of the operation has been successful. Should be unique.
|`withdrawnAmount`|The actual withdrawn amount. Will be completed after the withdraw has been successful
|`transferId`|The transfer id. Will be completed after the transfer step of the operation has been successful. Should be unique.
|`status`|The current status of the operation.
|`amount`|The amount transferred from and withdrawn of the technical account.
|`updatedAt`|The time of the last update

After creation, the properties should not change after their initial filling (excepted status and updatedAt). Therefore even thought you must implement the setters, you should not use these in your code. As they are interfaces, do no hesitate to implement them into existing entity classes.

#### Entity manager interfaces
The concrete implementation of these interfaces will be used to create, find (read) and update the entities they are related to. There are one for each of the entities.

###### Vendor/ManagerInterface
The `ManagerInterface` under the vendor namespace is obviously related to the vendor entity. Below some explanation for the methods implementation :

|Method name|Implementation advice|
|-----------|---------------------|
|`create`|This method should simply create the VendorObject. It must not save the vendor in the storage. It may be empty (i.e. the properties have not been set) as I set them afterwards anyway.|
|`update`|This method should update the vendor for another payment cycle than its creation one.  It must not save the vendor in the storage.
|`save`|This is the method who should be used to record one vendor in the storage.
|`saveAll`|This method is there to save an array of Vendor. As you receive a batch of vendor, they may be new vendors and already recored vendors in it.
|`findBy*`|Find a (and only one as all mandatory vendor properties are unique) vendor by the property given in the method name. Should return null if not found.
|`isValid`|Used to implement custom validation before save.

###### Operation/ManagerInterface
Analogously, the `ManagerInterface` below the namespace Cashout/Operation will handle operations. The create, save, saveAll and isValid method are the same as before only transposed to operations. Below some explanation for the other methods implementation :

|Method name|Implementation advice|
|-----------|---------------------|
|`generate*label`|These methods should return a string who will be used as a label for the HiPay transfer and withdraw API calls. Avoid using a non alphanumeric character|
|`findByWithdrawalId`|This method should return one and only one operation. If not found, return null.
|`findByMiraklIdAndPaymentVoucher`|This method should return at most one operation. If not found, return null. This method is used to check if an operation already existed for the given miraklId and paymentVoucher. It follows the mandatory unicity of the couple mirkalId & paymentVoucher.
|`findByStatus`|This method should return all operations who are in the given status.
|`findByStatusAndBeforeUpdatedAt`|This method should return all operations who are in the given status and who have the updatedAt property at or before the given date.

#### Configuration Interfaces

These interfaces must be implemented to create the instance of the classes who communicate with an external system (HiPay, Mirakl and FTP). There are only getters however all must be implemented. They correspond to the parameters need to instantiate these classes.

#### Model interfaces

There is only one interface in this category. The `TransactionValidator` is used to validate the transaction before computing them in an operation to be recorded (saved in a database or in a file for exemple). The sole method to implement, `isValid`, should do the validation (returning true if it passes).

### Event

You can easily interact with the processor execution flow thanks to the event system used throughout the process method. Based on the Symfony component Event Dispatcher, you can customize the behavior of the program when an event is dispatched.  The `AbstractProcessor` class expose two method `addListener` and `addSubscriber`. These two methods reflect the ones from the `EventDispatcherInterface`. This system avoid to have to create a new class inheriting the previous one.
Please consult this component documentation at http://symfony.com/doc/current/components/event_dispatcher/ for more information.

#### VendorProcessor

| Name                            | Event object class  | Description                                                                       |
|---------------------------------|---------------------|-----------------------------------------------------------------------------------|
| `before.vendor.get`             | N/A                 | Before the call to Mirakl to fetch the vendors                                    |
| `after.vendor.get`              | N/A                 | After the successful call to Mirakl to fetch the vendors                          |
| `before.availability.check`     | `CheckAvailability` | Before the call to hipay to check the availability of the email address           |
| `after.availability.check`      | `CheckAvailability` | After the successful call to hipay to check the availability of the email address |
| `before.wallet.create`          | `CreateWallet`      | Before the call to hipay to create the wallet                                     |
| `after.wallet.create`           | `CreateWallet`      | After the successful call to hipay to create the wallet                           |
| `before.bankAccount.add`        | `AddBankAccount`    | Before the call to hipay to send the bank account                                 |
| `after.bankAccount.add`         | `AddBankAccount`    | After the successful call to hipay to send the bank account                       |
| `check.bankInfos.synchronicity` | `CheckBankInfos`    | Used to add custom bank info validation                                           |

#### CashoutProcessor

| Name            	| Event object class 	| Description                                                                                                                                                         	|
|-----------------	|--------------------	|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------:	|
| `before.transfer` 	| `OperationEvent`     	| Before the call to hipay to transfer the amount from the technical account to the vendor/operator account                                                           	|
| `after.transfer`  	| `OperationEvent`     	| After the successful call to hipay to transfer the amount from the technical account to the vendor/operator account. The transferId will be set on the event object 	|
| `before.withdraw` 	| `OperationEvent`     	| Before the call to hipay to withdraw the amount from wallet to the actual bank account                                                                              	|
| `after.withdraw`  	| `OperationEvent`     	| After the call to hipay to withdraw the amount from wallet to the actual bank account. The withdrawId will be set on the event object                                                                                                                      	

#### Exception

Most of the custom exception dispatch an event with a specific name. Remember : there is no need to log yourself as there always a call to the logger done before the dispatch of the event. The event object class will always be the same : `ThrowException`. It is nothing more than a wrapper object to contain the exception.

|  Event name                       |Exception class                      |
|-----------------------------------|-------------------------------------|
|  `operation.already.created`      |`AlreadyCreatedOperationException`   |
|  `bankAccount.creation.failed`    |`BankAccountCreationFailedException` |
|  `ftp.upload.failed`              |`FTPUploadFailed`                    |
|  `invalid.bankInfo`               |`InvalidBankInfoException`           |
|  `invalid.operation`              |`InvalidOperationException`          |
|  `invalid.vendor`                 |`InvalidVendorException`             |
|  `not.enough.funds`               |`NotEnoughFunds`                     |
|  `no.wallet.found`                |`NoWalletFoundException`             |
|  `transaction.exception`          |`TransactionException`               |
|  `unauthorized.property.modified` |`UnauthorizedModificationException`  |
|  `wallet.unidentified`            |`UnidentifiedWalletException`        |
|  `bank.account.unconfirmed`       |`UnconfirmedBankAccountException`    |
|  `validation.failed`              |`ValidationFailedException`          |
|  `wrong.wallet.balance`           |`WrongWalletBalance`                 |

## Notification handler

The goal of this class is to handle the server-to-server notification sent by HiPay. It is sent by POST. There is one main function : `handleHiPayNotification`.  

This is the method to call in the web entry point. This entry point should be accessible to HiPay and you must give them its address. Mainly, it gets a string of valid XML, parse it, validate the md5 and invoke a function according to  the operation field of the notification. This method also uses the event system to allow an easy modification of the behavior for the action to take relative to the notification sent. There is however a default comportment for the withdraw notification. It sets the status of the operation with the sent `withdrawID` to success or failure according to the content of the request.
You have to handle the fetching of the XML from the request and the different exceptions that may arise.

| Name                                      | Event object class         | Description                                                       |
|-------------------------------------------|----------------------------|-------------------------------------------------------------------|
| `bankInfos.validation.notification.success` |`BankInfoNotification`| Sent when operation is bank_info_validation and its status is OK  |
| `bankInfos.validation.notification.failure` |`BankInfoNotification`| Sent when operation is bank_info_validation and its status is NOK |
| `identification.notification.success`       |`IdentificationNotification`| Sent when operation is identification and its status is OK        |
| `identification.notification.failure`       |`IdentificationNotification`| Sent when operation is identification and its status is NOK       |
| `other.notification.success`                | `OtherNotification`          | Sent when operation is other_transaction and its status is OK     |
| `other.notification.failure`                | `OtherNotification`          | Sent when operation is other_transaction and its status is NOK    |
| `withdraw.notification.success`             | `WithdrawNotification`       | Sent when operation is withdraw_validation and its status is OK   |
| `withdraw.notification.failure`             | `WithdrawNotification`       | Sent when operation is withdraw_validation and its status is NOK  |


# Test
There are some unit tests made. They were made with PHPUnit.
Before running the tests ensure that all dependencies are met. To do so run :

    $ composer install --dev

To run the test got to `tests` folder and run :

    $ phpunit

There should not be any errors.

# Versioning

This library is following Semantic Versioning v2.0.0. Please see their documentation for more information.

# Annex

All operation statuses are constants from the class Cashout/Model/Operation/Status.
| Name               | Value | Description                                    |
|--------------------|-------|------------------------------------------------|
| CREATED            | 1     | The operation was created                      |
| TRANSFER_SUCCESS   | 3     | The transfer has been executed with success    |
| TRANSFER_FAILED    | -9    | The transfer has been executed and failed      |
| WITHDRAW_REQUESTED | 5     | The withdrawal has been requested with success |
| WITHDRAW_SUCCESS   | 6     | The withdrawal has been executed with success  |
| WITHDRAW_FAILED    | -7    | The withdrawal has been executed and failed    |
| WITHDRAW_CANCELED  | -8    | The withdrawal has been cancelled              |

Comprehensive list of the interfaces to implement to use the library (not for the integration)
| Classpath                                  | Needed for                           | Description                                                                                  |
|--------------------------------------------|--------------------------------------|----------------------------------------------------------------------------------------------|
| `Api\HiPay\ConfigurationInterface`           | All                                  | Represents the data for a HiPay connection                                                   |
| `Api\Mirakl\ConfigurationInterface`          | All                                  | Represents the data for a Mirakl connection                                                  |
| `Service\Ftp\ConfigurationInterface`         | VendorProcessor                      | Represents the data for a FTP connection                                                     |
| `Vendor\Model\VendorInterface`               | All                                  | Represents an entity to send or receive money                                                |
| `Vendor\Model\ManagerInterface`              | All                                  | Manages vendor entities                                                                      |
| `Cashout\Model\Operation\OperationInterface` | CashoutInitializer, CashoutProcessor | Represents a transfer from the technical account and a withdrawal to the vendor bank account |
| `Cashout\Model\Operation\ManagerInterface`   | CashoutInitializer, CashoutProcessor | Manages the operation entities                                                               |