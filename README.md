# AcmeClient library

PHP library implementing the Automatic Certificate Management Environment (ACME) protocol,
used for requesting certificates from services like Let's Encrypt.

The goal of the project is to create a flexible library that allows you to incorporate certificate registration 
though ACME, without the need of an external tool or client. The library provides interfaces that you can implement
in your own classes, and then have the client use those classes. 

Don't really need custom classes but still want to control from within your own project? The library comes with a
set of default classed that implement the said interfaces. 

Note: the actual validation is not part of the scope of this library. It will tell you what http or DNS challenge 
to provide, but it's up to you to make sure the given values end up where they need to end up.

# (in)completeness

This does not aim to be a full implementation of the RFC, but mainly focuses on the flow of obtaining certificate
from ACME compatible services (eg. Let's Encrypt).

# Work in progress - alpha vibes right here

This is very much a work in progress. All classes and interfaces are bound to change without notice
until I like it the way that suits me best ;) At this point, use for reference / ideas only.

## Currently implemented
- Account creation
- Create order
- Authorize order
  - Get authorizations
  - Get challenges

## Not yet implemented but definitely going to
- Validate challenges
- Check authorization status
- Get certificate

# References
https://datatracker.ietf.org/doc/html/rfc8555
