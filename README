MCMA WHMCS Plugin
=================
This WHMCS plugin will allow (via MCMA) users to be assigned to groups based on purchased products. This is used for
communities who give elevated access to donors etc.

The plugin uses the JSON MCMA API for communication.

Install
-------
* Download code from [GitHub](https://github.com/DamianZaremba/McMyAdmin-WHMCS/tarball/master).
* Untar into {{whmcs root}}/modules/servers/mcma/
* Create a server and complete the following details:
	* Name
	* Hostname
	* IPAddress
	* Type: Mcma
	* Username: MCMA username
	* Password: MCMA password
* Create a new email template:
	* Type: Product
	* Unique Name: MCMA Welcome Email
		* From: {{company name}} {{company email}}
		* Subject: Minecraft Account Information
		* Content:<pre><code>
			Dear {$client_name},
			Thank you for your order from us! Your account has now been setup, you may need to re-login for the permissions to take effect.
			{$signature}
			(Feel free to customize the messages for your own use)</code></pre>
* Create a product with the following details:
	* Details > Product name
	* Details > Product type: Other
	* Details > Require Domain: none
	* Details > Welcome Email: MCMA Welcome Email
	* Module Settings > Module Name
	* Module Settings > Group Name: Enter the MCMA group you want the user of this product to be added to
	* Module Settings > Whitelist user: Should the user get added to the whitelist
	* Custom Fields > Add new custom field with the following details:
		* Field Name: Minecraft username
		* Field Type: Text Area
		* Required Field: x
		* Show on Order Form: x
		* Validation: [a-zA-Z0-9_]+

Authors
-------
Damian Zaremba <damian@damianzaremba.co.uk>

License
-------
Copyright (c) 2011 Damian Zaremba <damian@damianzaremba.co.uk>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

Changelog
---------
_ Version 0.1 _
* Initial version
* Supports:
	* Optional whitelisting
	* Adding users to groups via WHMCS
* Removes users from mcma groups on terminate and suspend
* Adds users into mcma groups on create and un-suspend
* Has a force sync button in case of un-synced stuff

Known bugs:
* None
