# **SpectroCoin-Merchant-PHP**

Sample PHP API client for communicating with [SpectroCoin](https://spectrocoin.com) Merchant API service. This text has been generated based on the official [documentation](https://spectrocoin.com/integration/spectrocoin.html) provided by Spectrocoin.

## Contents

### /SCMerchantClient

`SCMerchantClient.php` is SpectroCoin Merchant API sample PHP client. You should use it as it is or update it for your needs. This folder contains generic API client part.

### /cert

Folder contains test API certificates.

### /js and root

The other folders or files are used only for API client testing purpose.

Use <em>direct.php</em> for initiating a direct payment transaction and processing payment details.

Use <em>redirect.php</em> for handling payment redirection and completing the payment process.

## Setting up

1. [Sign up](https://auth.spectrocoin.com/signup) for a Spectroin Account.
2. [Log in](https://auth.spectrocoin.com/login) to your Spectroin account.
3. On the dashboard, locate the ["Business"](https://spectrocoin.com/en/merchants/projects) tab and click on it.
4. Click on ["New project"](https://spectrocoin.com/en/merchants/projects/new).
5. Fill in the project details and select desired settings (settings can be changed).
6. The Private Key can be obtained by switching on the Public key radio button (Private key will not be visible in the settings window, and it will have to be regenerated in settings). Copy or download the newly generated private key.
7. In the PHP client, it is necessary to create the <em>mprivate.pem</em> and <em>mpublic.pem</em> files within the <em>/cert</em> directory. These files should be utilized to store the credentials that have been recently generated.
8. In <em>constants.php</em> fill merchant id and project id.
9. Click "Submit" to save the project.

## Information

This client has been dev`eloped by SpectroCoin.com If you need any further support regarding our services you can contact us via:

E-mail: info@spectrocoin.com </br>
Phone: +372 683 8000 </br>
Skype: spectrocoin_merchant </br>
[Web](https://spectrocoin.com) </br>
[Twitter](https://twitter.com/spectrocoin) </br>
[Facebook](https://www.facebook.com/spectrocoin/)
