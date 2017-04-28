# Version 2.5.2
# Version 2.5.1
- Fix array_push

# Version 2.5.0

- New If Phone is empty it is not sent
- Fix Withdrawal: fatal error in isIdentified function
- Fix Customize the error level for wich emails should be sent
- Fix Error 401 in create account function
- Fix Error in vendor wallet list command
- Fix Error in transfert function

# Version 2.4.2
# Version 2.4.1

- Fix API Soap - Replace mergeLoginParameters() by mergeLoginParametersSoap()

# Version 2.4.0

- The login is no longer the email, it is now registered by the concatenation of the name of the shop and its ID
- Migration to REST API for Bank info management 
- Migration to REST API for account creation
- New management of callback notifications 

# Version 2.3.0

- Send a notification by Email when KYC is invalidated
- New mandatory fields for the bank Informations
- Remove all special characters when the bank informations are sent to Wallet
- Bugfix iso code Bank country 

# Version 2.2.3

Fix Transfers the vat number in the Wallet account creation 

# Version 2.2.2

Updates links to documentation.

# Version 2.2.1

README file update.

# Version 2.2.0

This version allows Mirakl shop emails to be updated.

# Version 2.1.1

This version fixes an issue with KYC files downloaded from Mirakl being parsed by the REST client. Instead, files are now downloaded in raw mode.

# Version 2.1.0
This version adds an option to filter shops by testing a regex on the payment lines' transaction number parameter during the cash-out initialization process.

# Version 2.0.3
Fixes synchronization issue with erroneous information being sent when creating the HiPay Wallet account.

# Version 2.0.2
Fixes issue occurring with manual invoice amounts not taken into account in the payment calculation. This raised a warning which is now fixed.

# Version 2.0.1
This version groups Mirakl files fetching by paquets (the web service has a limitation on the number of shops for which documents can be retrieved).

# Version 2.0.0
- This new version handles KYC documents upload from Mirakl to HiPay Wallet through REST API. You no longer need to set up a FTP server to upload those documents.
- Some notification messages have been clarified.

# Version 1.0.10
README file update.

# Version 1.0.9
Fix mistakes in README file.

# Version 1.0.8
Add Code climate badge.

# Version 1.0.7
README file update.

# Version 1.0.6
README file update.