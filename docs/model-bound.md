Model Bound Resources
=====================

Most real world APIs will have many REST resources that map directly to models. For resources like this
Rhubarb has a `ModelRestResource` which let's you create these resources very quickly.

A `ModelRestResource` combines the features of a collection resource and an item resource which allows
both 'views' to be configured in one class.

## Creating a `ModelRestResource`

`ModelRestResource` is abstract and so you must extend the class.

``` php
class ContactResource extends ModelRestResource
{
    public function getModelName()
    {
        // Return the name of the model to bind to.
        return "Contact";
    }
}
```

This example is a legitimate resource object for exposing a "Contact" model as a REST resource. To use the
resource we must register a URL handler for it. Because ModelRestResource objects can handle both collection
and item URLs we must use the RestCollectionHandler to register it as it is the RestCollectionHandler which
understands URLs with and without a final identifier.

``` php
// In app.config.php
$this->addUrlHandlers(
[
    "/contacts" => new RestCollectionHandler( '\MyAPI\Resources\ContactResource' )
] );
```

Assuming that the `Contact` model has a label column name defined in its schema, you should already have a
basic API for serving contacts:
 
``` javascript
GET /contacts

{
    "_href": "/contacts",
    "items": [
        {
            "_href": "/contacts/1",
            "_id": 1,
            "Name": "John Smith"
        },
        {
            "_href": "/contacts/2",
            "_id": 2,
            "Name": "Peter Salmon"
        },
        {
            "_href": "/contacts/3",
            "_id": 3,
            "Name": "Claire Blackwood"
        }
    ],
    "count": 3,
    "range": {
        "from": 0,
        "to": 2
    }
}

GET /contacts/3

{
    "_href": "/contacts/3",
    "_id": 3,
    "Name": "Claire Blackwood"
}
```

## Customising the resource item content

By default a `ModelRestResource` will extract the ID and, if configured in the model, the model's label column.
However it's rare that this is sufficient for an API and if you need to customise the list of properties
you should override `getColumns` and return a specific list of columns:

``` php
class ContactResource extends ModelRestResource
{
    public function getColumns()
    {
        // Let's keep the ID and label
        $columns = parent::getColumns();
        // Now add another property to our resource:
        $columns[] = "DateOfBirth";
        return $columns;
    }
}
```

``` javascript
GET /contacts

{
    "_href": "\/contacts",
    "items": [
        {
            "_href": "\/contacts\/1",
            "_id": 1,
            "Name": "John Smith",
            "DateOfBirth": "2015-07-15T00:00:00+0100"
        },
        {
            "_href": "\/contacts\/2",
            "_id": 2,
            "Name": "Peter Salmon",
            "DateOfBirth": "2015-07-14T00:00:00+0100"
        },
        {
            "_href": "\/contacts\/3",
            "_id": 3,
            "Name": "Claire Blackwood",
            "DateOfBirth": "2015-07-06T00:00:00+0100"
        }
    ],
    "count": 3,
    "range": {
        "from": 0,
        "to": 2
    }
}
```

Properties from the model can be renamed in the resource:

``` php
class ContactResource extends ModelRestResource
{
    public function getColumns()
    {
        // Let's keep the ID and label
        $columns = parent::getColumns();
        // Now add another property to our resource but call it DOB this time.
        $columns["DOB"] = "DateOfBirth";
        return $columns;
    }
}
```

> Try to avoid aliasing properties like this unless absolutely necessary. If possible fix a poor choice
> of column name in the model itself rather than aliasing it in the REST API.

You can also use callbacks to calculate values that aren't based on columns:

``` php
class ContactResource extends ModelRestResource
{
    public function getColumns()
    {
        // Let's keep the ID and label
        $columns = parent::getColumns();
        // Calculate a value in a call back.
        $columns["Age"] = function(){
            $tz  = new DateTimeZone('Europe/London');
            return $this->model->DateOfBirth
                                ->diff(new DateTime('now', $tz))
                                ->y;
        };
        
        return $columns;
    }
}
```

> Just as models abstract logic from your user interface, REST APIs provide one other layer of abstraction
> and also one more layer for adding calculated values like this. Try however to be consistent with which
> level you add these computed properties to. In this case it's a valid question as to whether Age should
> be calculated in the Contact model in which case it could be listed here as a normal 'Column'.

## Nested resources

You can include relationship properties in your `ModelRestResource` and you have three choices for how to do
this:

1. Embed the full resource as a 'child'
2. Embed a summary of the full resource as a 'child'. Includes an *_href* property so that the full object
   can be accessed later when needed
3. Embed a *'link'* node which just includes the *_href* property.

All three choices first require that the 'child' resource is mapped to the model returned by the relationship.
To setup the mapping you need to call the following in your app.config.php:

``` php
// Map the Organisation model to it's default RestResource object.
ModelRestResource::registerModelToResourceMapping( "Organisation", OrganisationResource::class );
``` 

> Note: The most common reason for nested resources not appearing is because the mapping is incorrect
> or has been omitted entirely.

### Nesting a full resource

This is the most simple way to nest a related model, however it means enlarging your resource object 
even if the data wasn't required. Some nested models can be large so you should think carefully before
doing this. To nest the related model simple include the navigation property in your `getColumns()` function.

``` php
class ContactResource extends ModelRestResource
{
    public function getColumns()
    {        
        $columns = parent::getColumns();
        
        // Include an embedded model 
        $columns[] = "Organisation";
        
        return $columns;
    }
}
```

This will simulate a full "get" request on our Organisation resource and embed the content under an
"Organisation" property.

> With fully nested resources there is a danger of creating an infinite nesting loop. A common example
> would be Organisation nesting a collection of Contact resources which in turn nest their Organisation
> resource, which in turn nests a collection of Contact resources etc.
>
> Presently there are no safeguards to prevent this however in many cases the issue is solved
> by using summaries or links. This is often a better solution anyway for resources that are
> complicated enough to expose the issue.

### Nesting a resource summary

This operates just like the full resource however it requests a summary of the resource rather than
a the full resource. To switch to a summary simply change the column mapping like this:

``` php
class ContactResource extends ModelRestResource
{
    public function getColumns()
    {        
        $columns = parent::getColumns();
        
        // Include an embedded model 
        $columns[] = "Organisation:summary";
        
        return $columns;
    }
}
```

To control the columns listed in a summary (again by default just the label and unique identifier)
simply override `getSummaryColumns()` in the relevant resource. This function mirrors the behaviour
of `getColumns()` as outlined above.

The `_href` property is also included so should the user require the full resource the can make a 
second GET request on that URL.

### Nesting a link to a resource

The third approach returns only the `_href` property.
 
``` php
class ContactResource extends ModelRestResource
{
    public function getColumns()
    {        
        $columns = parent::getColumns();
        
        // Include an embedded model 
        $columns[] = "Organisation:link:/organisation";
        
        return $columns;
    }
}
```

If the nested resource is a collection or the resource doesn't have a canonical link then you must supply
a URL suffix to append to the URL of the current resource. In our example there is no canonical URL
for organisation resources so we instruct the link to use the current URL appended with "/organisation".
i.e. /contacts/3/**organisation**

Of course you must also make sure that you have a URL handler configured to handle this URL.

## Filtering Collections

In all but the most basic of applications a collection of items will need filtered to those appropriate for
the authenticated user. This is no different in a REST API and security must be considered carefully when
designing your API.

For custom RestResource objects you should handle security in the `get` methods manually. Throw a
`RestAuthenticationException` or similar if the user should not have access to the requested resource.
 
Because ModelRestResource objects handle both the collection and item resources we can control access to the item
resources by carefully filtering the collections. When requesting an item Rhubarb checks to see if the
item is contained in the collection and if not an exception is thrown.

There are four filtering methods you can override:

`filterModelCollectionAsContainer(Collection $collection)`
:   Override this method to filter the collection to the set of generally allowed items. For example a
    resource to provide "In Progress" orders would apply the status filter to the orders collection in this
    method.

`filterModelCollectionForQueries(Collection $collection)`
:   If your resource supports filtering the list based on HTTP query parameters this is where you should
    do it. For example a Staff resource might support searching by adding "?name=alice" to the GET URL.

`filterModelCollectionForSecurity(Collection $collection)`
:   Apply filters here to remove items the currently authenticated used should not have access to. Normally this
    looks at the default login provider and applies a relevant criteria.

`filterModelCollectionForModifiedSince(Collection $collection, RhubarbDateTime $since)`
:   In order to support the "If-Modified-Since" HTTP header you should apply the relevant filter based upon the
    $since RhubarbDateTime argument passed to this function.
    
> The four methods exist as a pattern to allow you to create parent classes without requiring
> extenders to call the parent implementations. For example you might have a parent class that
> implements some site wide security filters in `filterModelCollectionForSecurity` but in a 
> child class you need to implement some query filters. By asking the developer to extend
> `filterModelCollectionForSecurity` you know that the developer can't accidentally stop
> the security filters being applied.

Each of the methods work the same way - take the Collection object passed to it and apply
some model filters using the `filter()` method:

``` php
class ContactResource extends ModelRestResource
{
    protected function filterModelCollectionForSecurity(Collection $collection)
    {
        parent::filterModelCollectionForSecurity($collection);
        
        // Get the logged in customer
        $login = LoginProvider::getDefaultLoginProvider();
        $customer = $login->getModel();
        
        // Make sure we can only see that customer's contacts
        $collection->filter(new Equals("CustomerID", $customer->UniqueIdentifier));
    }
} 
```

## Responding to PUT and POST

PUT and POST are both handled by the ModelRestResource to transparently create new model items or update
existing ones. Sometimes however you need to cause specific functionality to happen during these events.
There are three methods you can override to handle this:

beforeModelUpdated($model, $restResource)
:   Called just before the model is actually updated. You have access to both the Model object and the
    payload passed to the API
    
afterModelUpdated($model, $restResource)
:   Called just after the model is actually updated. You have access to both the Model object and the
    payload passed to the API
    
afterModelCreated($model, $restResource)
:   Called just after a model is created. You have access to both the Model object and the payload passed
    to the API