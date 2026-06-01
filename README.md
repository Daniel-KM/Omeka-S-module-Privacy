No Google Chrome Flock Tracking (module for Omeka S)
====================================================

> ***IMPORTANT***: This module is deprecated and has been replaced by the module
> [Privacy] and won’t be updated any more. The upgrade from it is automatic.

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[No Google Chrome Flock Tracking] is a module for [Omeka S] that adds a HTTP
header to forbid Google to track visitors via Chrome and derivative browsers.

Indeed, with the new versions of Chrome and derivative browsers, Google steals
directly your browsing history even if you forbid it, creates a profile, and
gives access to it to any other tracking tool via a unique flock identifier
(abandoned) or via a list of browsing interests.

The [Topics API] is not a web standard because it is rejected by privacy
supporters like Mozilla (Firefox) and Apple (Safari), but supported by Google
and implemented in Chrome and derivative browsers, mainly Microsoft Edge.

**Remember**: the only difference between Google (the same for Facebook,
Microsoft, Apple, etc.) and any hacker on the web is the fact that you checked a
box (or not) to agree CGU (regularly updated without your real consent) some
years ago when you installed it.


Note on the module and privacy
------------------------------

Originally the module disabled the [FLoC] ("Federated Learning of Cohorts") API
through the `interest-cohort` directive of the `Permissions-Policy` header.
FLoC was abandoned by Google in 2022 and replaced by the [Topics API], part of
the so-called ironically "Privacy Sandbox" (the privacy is protected against
hackers, **except** Google, ad sellers that buy your data through Google, and
any American state agency via the Patriot Act). The Topics API still profiles
visitors from their browsing history inside Chrome and shares topics with
advertisers.

This tracking remains illegal in most countries (GDPR / ePrivacy), whatever
Google says, but this header is required to explicitly forbid Google to include
the visitor activity on this site in their interest profile.

This module can be used in conjunction with the module [EU Cookie Bar], that
warns about the tracking by cookies when Google Analytics or Facebook buttons
are used to steal data of your visitors.

This module is useless if you can access the config of your server or if you
can add the line below in the Apache file ".htaccess" at the root of Omeka.
The Topics API is opted out site-wide by sending the header:

```.htaccess
Header always set Permissions-Policy: browsing-topics=()
```

The module simply adds this line automatically if not present, after some
checks. If a previous FLoC directive (`interest-cohort=()`) is found, it is
upgraded in place to `browsing-topics=()`. Once installed, the module can be
removed. Of course, it is better to include the line above in the main config
files of Apache.

Important: The Apache module "headers" must be enabled first:

```sh
sudo a2enmod headers
sudo systemctl restart apache2
```

Nginx and other servers are currently not supported. The equivalent directive
is the same header value, set at the server level.

Note that the header must be added to any response, not only to the Omeka
ones: if the assets (images, js, css…) are not protected, Google will add (or
maybe not) the site in the visitor profile anyway.


Installation
------------

See general end user documentation for [installing a module].

* From the zip

Download the last release [NoGoogleChromeFlockTracking.zip] from the list of releases, and
uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `NoGoogleChromeFlockTracking`.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Daniel Berthereau, 2021-2026 (see [Daniel-KM] on GitLab)


[Privacy]: https://gitlab.com/Daniel-KM/Omeka-S-module-Privacy
[No Google Chrome Flock Tracking]: https://gitlab.com/Daniel-KM/Omeka-S-module-NoGoogleChromeFlockTracking
[Omeka S]: https://omeka.org/s
[Topics API]: https://developer.mozilla.org/en-US/docs/Web/API/Topics_API
[FLoC]: https://amifloced.org
[EU Cookie Bar]: https://gitlab.com/Daniel-KM/Omeka-S-module-EUCookieBar
[installing a module]: https://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[NoGoogleChromeFlockTracking.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-NoGoogleChromeFlockTracking/-/releases
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-NoGoogleChromeFlockTracking/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: https://opensource.org
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
