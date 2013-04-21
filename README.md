SilverStripe DataObject Cruft
=============================

This module provides a tool in DatabaseAdmin for analyzing and removing parts of the database schema which are left-over from earlier development or removed modules (a.k.a. [cruft](http://en.wikipedia.org/wiki/Cruft)).

Requirements
------------

- SilverStripe 3.0+

Installation
------------

This module can be installed using composer, by adding the package [thefrozenfire/dataobjectcruft](https://packagist.org/packages/thefrozenfire/dataobjectcruft), or by cloning this repository to the folder `dataobjectcruft`. It is recommended that you not install this package in your production environment, as its security impact has not been evaluated.

Usage
-----

Once installed, run /dev/build to ensure that the necessary extensions are loaded. Simply visit /dev/scrub to view tables, fields, and indexes which have been identified as not being part of the SilverStripe-generated schema.

Before proceeding with any use of this tool, you are urged to ensure that you have recent backups which you can restore from, in case something goes wrong.

Not all items listed in this interface are guaranteed to be safely removeable. There are certain situations involving an overloaded `DataObject::requireTable`, or a `DataExtension::augmentDatabase`, which can result in additional schema data being generated. Please inspect each schema item that is listed, and only select those items which you are sure are not necessary.

Once you have selected each item which you wish to removed, click the "Delete Cruft" button. This will remove all selected schema data.
