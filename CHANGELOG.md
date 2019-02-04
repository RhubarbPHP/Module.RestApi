# Change Log

### 3.0.0

Changed:    Total Slim down - switched to [Slim framework](https://www.slimframework.com).
This module is now a wrapper and a bit of tooling around a Slim app.

### 2.0.8

Changed:    Support for including null values on rest resources.

### 2.0.7

Changed:    Changed CredentialsLoginProviderAuthenticationProvider to throw the CredentialsFailedException

### 2.0.6

Changed:    Support for CredentialsLoginProvider
Added:      Codeception 

### 2.0.5

Added:      Allowing rest resources to specify headers on the response

### 2.0.4
Fixed:      Rest resource filter methods do not need to return a collection

### 2.0.3

Fixed:      2.0.2 introduced issue with ModelRestResources using callbacks in the columns array

### 2.0.2

Fixed:      Can only POST/PUT exposed columns

### 2.0.1

Added:      IpRestrictedAuthenticationProvider

### 2.0.0

Changed:    Decoupled response object from method handlers
Added:      XML request/response support
Changed:    CollectionRestResource::listItems can now be overridden

### 1.0.2

Changed:    getItemResourceForModel changed to protected to allow overriding

### 1.0.1

Fixed:		Incompatability with new stem version

### 1.0.0

Added:		Changelog
Fixed:      Issue with collection counts on collection resources
Changed:    RestClient is a bit more extensible
Changed:    TokenAuthenticatedRestClient is a bit more extensible
Changed:	Support for Rhubarb 1.0.0
