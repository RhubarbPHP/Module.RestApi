# Change Log

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
