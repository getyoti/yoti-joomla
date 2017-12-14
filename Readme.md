# Yoti Joomla SDK #

Welcome to the Yoti Joomla SDK. This repo contains the tools you need to quickly integrate your Joomla back-end with Yoti, so that your users can share their identity details with your application in a secure and trusted way.    

## Table of Contents

1) [An Architectural view](#an-architectural-view) -
High level overview of integration

2) [References](#references)-
Guides before you start

3) [Installing the SDK](#installing-the-sdk)-
How to install our SDK

4) [Extension Setup](#extension-setup)-
How to set up the extension in Joomla

5) [Linking existing accounts to use Yoti authentication](#linking-existing-accounts-to-use-yoti-authentication)

6) [API Coverage](#api-coverage)-
Attributes defined

7) [Yoti Docker](#yoti-docker)
How to set up Yoti Docker module

8) [Support](#support)-
Please feel free to reach out

## An Architectural view

Before you start your integration, here is a bit of background on how the integration works. To integrate your application with Yoti, your back-end must expose a GET endpoint that Yoti will use to forward tokens.
The endpoint can be configured in the Yoti Dashboard when you create/update your application. For more information on how to create an application please check our [developer page](https://www.yoti.com/developers/documentation/#login-button-setup).

The image below shows how your application back-end and Yoti integrate into the context of a Login flow.
Yoti SDK carries out for you steps 6, 7 and the profile decryption in step 8.

![alt text](https://github.com/getyoti/yoti-joomla/raw/master/login_flow.png "Login flow")


Yoti also allows you to enable user details verification from your mobile app by means of the Android (TBA) and iOS (TBA) SDKs. In that scenario, your Yoti-enabled mobile app is playing both the role of the browser and the Yoti app. Your back-end doesn't need to handle these cases in a significantly different way. You might just decide to handle the `User-Agent` header in order to provide different responses for desktop and mobile clients.

## References

* [AES-256 symmetric encryption][]
* [RSA pkcs asymmetric encryption][]
* [Protocol buffers][]
* [Base64 data][]

[AES-256 symmetric encryption]:   https://en.wikipedia.org/wiki/Advanced_Encryption_Standard
[RSA pkcs asymmetric encryption]: https://en.wikipedia.org/wiki/RSA_(cryptosystem)
[Protocol buffers]:               https://en.wikipedia.org/wiki/Protocol_Buffers
[Base64 data]:                    https://en.wikipedia.org/wiki/Base64

## Installing the SDK

To import the Yoti Joomla extension inside your project:

1) Log on to the admin console of your Joomla website. e.g. https://www.joomladev.com/administrator
2) Navigate to at `Extensions-> Manage -> Install` and do one of the following:
- click `Install from Web` link and Search for `Yoti`
- click `Upload Package File` and upload the downloaded zip file from [our extension page](https://extensions.joomla.org/extensions/extension/access-a-security/yoti/).
- you can also `Install from URL` or `Install from folder`
3) Install and enable `Yoti login` module and `Yoti - User profile` plugin.

## Extension Setup

To set Yoti up follow the instruction below:

1) Navigate on Joomla to the Components-> Yoti (details in `Yoti Components settings` below)
2) Navigate to `Extensions -> Modules`, search for `Yoti Login` module and enable it.
3) Navigate to `Extensions -> Plugins`, search for `Yoti - User Profile` plugin and enable it.

### Yoti Components Settings 

You will be asked to add the following information:
 
- `Yoti App ID` is unique identifier for your specific application.

- `Yoti Scenario ID` Used to render the inline QR code.

- `Yoti SDK ID` is the SDK identifier generated by Yoti Dashboard in the Key tab when you create your app. Note this is not your Application Identifier which is needed by your client-side code.

- `Company Name` this will replace `Joomla` wording in the warning message which is displayed on the custom login form.

- `Yoti PEM File` is the application pem file. It can be downloaded only once from the Keys tab in your Yoti Dashboard.

Please do not open the pem file as this might corrupt the key and you will need to create a new application.

## Linking existing accounts to use Yoti authentication

To allow your existing users to log in using Yoti instead of entering thier username/password combination, there is a tick box when installing the module which allows Yoti accounts to link to email addresses.

## API Coverage

* Activity Details
    * [X] User ID `user_id`
    * [X] Profile
        * [X] Photo `selfie`
        * [X] Given Names `given_names`
        * [X] Family Name `family_name`
        * [X] Mobile Number `phone_number`
        * [X] Email address `email_address`
        * [X] Date of Birth `date_of_birth`
        * [X] Address `postal_address`
        * [X] Gender `gender`
        * [X] Nationality `nationality`
        
## Yoti Docker
This is a Docker module for Joomla including Yoti extension.  

### Setup
To try out our Docker module, clone this repos and run the following commands:

`cd yoti-joomla` if this is the directory where you cloned the repos

`docker-compose build --no-cache` to rebuild the images if you have modified `docker-compose.yml` file

`docker-compose up -d` to build the containers.    

After the command above has finished running, browse the link below and follow the instructions  

`http://localhost:8080`

### Database Configuration
When prompted, enter the following details for the database:

Host Name `joomladb`

Username `root`

Password `root`

Database Name `yotijoomla`

Table Prefix `yoti_`

### Register and enable Yoti extension
Please register Yoti extension which is installed along side Joomla CMS, by running the command below to process the SQL dump.

`docker exec -i yotijoomla_joomladb_1 mysql -uroot -proot yotijoomla < ./docker/mysql-dump.sql`

After running the command above, please follow the instructions in our [extension setup](#extension-setup) section to set it up.        

## Support

For any questions or support please email [sdksupport@yoti.com](mailto:sdksupport@yoti.com).
Please provide the following the get you up and working as quick as possible:

- Computer Type
- OS Version
- Screenshot
