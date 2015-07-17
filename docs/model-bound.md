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

> Try to avoid aliasing properties like this unless absolutely necessary. It's much better to rename
> the actual column name in the model to stop people searching needlessly for properties they'll never
> be able to find...

## Related models

You can include relationship properties in your `ModelRestResource` and you have three choices for how to do
this:

1. Embed the full resource as a 'child'
2. Embed a summary of the full resource as a 'child'. Includes an *_href* property so that the full object
   can be accessed later when needed
3. Embed a *'link'* node which just includes the *_href* property.

## Embedding the full resource

This is the most simple way to access a related model, however it means enlarging your resource object 
even if the data wasn't required. Some related models can be large so you should think carefully before
doing this. To embed the related model simple include the navigation property in your `getColumns()` function.

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