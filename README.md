# PayEx Payment Method - Magento1.X Extension

PayEx payment method acts as an intermediary between your Magento 1.X store and the vast payment processing networks.

## Supported Magento Versions: 
1.7+, 1.8+, 1.9+

## Installation Guide

### Step 1: Uploading The Extension

Before uploading the extension make sure that you have above mentioned Magento Community Edition. You can diectly download the extension package or clone the respository. 

There are two ways to upload/install the extension in your store:

A. Upload via cPanel File Manager

From your cPanel dashboard go to File Manager --> DomainName.com. On the Menu above, click on Upload and then click the Browse button and locate PayEx folder that is inside your master branch 'Magento'. Double click the file to select it and
then click the Upload button. If you prefer uploading the theme via FTP you will need to upload the theme via FTP Software as explained below.

### Step 2. Upload via FTP

You need to use an FTP client such as FileZilla. To set up your FTP client to connect to your website see: http://www.aschroder.com/2010/05/installing-a-magentoextension-
manually-via-ftp-or-ssh/

Upload the contents of PayEx folder to your Magento Root directory.

## General Questions

### Question 1:  How to find out PayEx configuration settings page?

To get the PayEx configuration section, Use these steps:

Admin > System > Configuration > Sales > Payment Methods > PayEx | Your Online Payment System

Under this section, you can add, modify and delete the PayEx settings. This is the place where you can take control of the entire configuration for your PayEx extension.

### Question 2:  How do I setup Payment modules – PayEx Payment Method?

Once you have an account with PayEx, you’ll need to obtain values for the following fields:

1. PayEx Merchant Account Number

2. PayEx Online URL (This URL will be provided by PayEx)

3. Encryption Key

To find these, follow these steps:

### Question 3: How to enable the Payment Module:

While still in the “PayEx | Your Online Payment System” area of System > Configuration, change the “Enabled” dropdown (first setting) to “Yes“.

Please contact at ravisinghengg@gmail.com for any suggestions/query. I'll try to get back to you as soon as i can.
