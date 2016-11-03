2Performant PHP API
===================

The API allows you to integrate any 2Performant network in your application. It's goal is to make sure developers can implement anything that can be done via the web interface using API functions.

The API is RESTful JSON over HTTP using all four verbs (GET/POST/PUT/DELETE) using [Guzzle](http://docs.guzzlephp.org/en/latest/) as a HTTP client.

API documentation can be found at:
http://doc.2performant.com/

The PHP wrapper uses `camelCase` notation rather than the `underscore_notation` used in the HTTP API, but changes are transparent to the user using the wrapper.


Installation
============

It's recommended to install the library using [Composer](http://getcomposer.org/).

        curl -sS https://getcomposer.org/installer | php

Then, install the library (along with its dependencies)

        php composer.phar require 2Performant/2Performant-php

After that, use the composer autoloader in your PHP script

        <?php

        require 'vendor/autoload.php';

All done! Now you can use the classes provided, included in the `TPerformant\API` namespace.


Usage Examples
==============

Interacting with 2Performant is very easy.

First you log in

        // As an advertiser
        use TPerformant\API\HTTP\Advertiser;

        ...

        $me = new Advertiser('affiliate.manager@somecompany.com', 'password'); // fill in with your own credentials

        $commissions = $me->getCommissions();

        // or as an affiliate
        use TPerformant\API\HTTP\Affiliate;

        ...

        $me = new Affiliate('awesome.affiliate@affiliatesite.com', 'password'); // fill in with your own credentials

        $commissions = $me->getCommissions();


For details about each API function the documentation can be found at:
https://github.com/2Parale/2Performant-php/wiki

Commom issues
=============

If you encounter issues with SSL (certificate-related connection problems), please try [updating your cURL CA root certificates](https://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/).


Reporting Problems
==================

If you encounters any problems don't hesitate to contact us at:
support (at) 2performant.com
