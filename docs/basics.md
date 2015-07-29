Basics of REST API design in Rhubarb
====================================

To create a REST resource you need to extend the `RestResource` class. A RestResource object is the most basic
type of REST object. It knows that it needs an href property and provides a pattern for dealing with
GET, POST, PUT, HEAD and DELETE verbs.

When you extend RestResource you must create a getRelativeUrl() method which should return the relative URL for this
resource (depending on how this resource is served the full "href" property maybe different)

``` php
class WeatherResource extends RestResource
{

}
```

Now we need to get our resource to response to http verbs. Let's implement GET:

``` php
class WeatherResource extends RestResource
{
    public function get()
    {
        // Start with the 'skeleton'. This gives us a stdClass object with the href already populated.
        $resource = $this->getSkeleton();

        $resource->Outlook = "cloudy";
        $resource->MaxTemp = 22;
        $resource->MinTemp = 7;

        return $resource;
    }
}
```

REST resources are retrieved using URLs and so are made visible in Rhubarb using Urlhandler objects like
all other URLs. Edit your app.config.php and register some the handler:

``` php
$this->addUrlHandlers(
[
    "/weather" => new RestResourceHandler( '\MyAPI\Resources\WeatherResource' )
] );
```

Requesting the resource in the browser should now give you the following output:

``` javascript
{
    _href: "/weather",
    Outlook: "cloudy",
    MaxTemp: 22,
    MinTemp: 7
}
```

## Item resources

If you need to represent a resource that has a unique identifier, you should extend `ItemRestResource` instead.
This class adds one special property to the output "_id" which is can be used in passing to other requests etc.

``` php
class DayOfTheWeek extends ItemRestResource
{
    public function get()
    {
        // Start with the 'skeleton'. This gives us a stdClass object with the href already populated.
        $resource = $this->getSkeleton();

        // Silly example but just switch on the ID and return the correct day of the week.
        $days = [ "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun" ];

        // $this->id contains the identifier.
        $resource->Day = $days[ $this->id ];

        return $resource;
    }
}
```

In order to serve an item resource you must however use a RestCollectionHandler. This handler knows to expect
and ID on the URL and passes it to the resource (thereby ending up in $this->id).

``` php
$this->addUrlHandlers(
[
    "/day-of-the-week" => new RestCollectionHandler( '\MyAPI\Resources\DayOfTheWeek' )
] );
```

Requesting /day-of-the-week/1 in the browser should now give you the following output:

``` javascript
{
    _href: "/day-of-the-week",
    _id: "1",
    Day: "Tue"
}
```

## Collection resources

To present a collection of items in a single resource extend the CollectionRestResource. Collection resources
still get an href property, but instead of the key value pairs of an item it has a sub node called **"items"**
which is an array of the matching items. It also has a count property and because it will normally limit the
collection to 100 items a range property tells you which section of the full list you're currently viewing.

Instead of implementing the `get()` function you implement the `getItems()` function instead.

Here is the collection form of our days of the week resource.

``` php
class DaysOfTheWeek extends CollectionRestResource
{
    protected function getItems($from, $to, RhubarbDateTime $since = null)
    {
        // Ignoring $since as it has no bearing in this case.
        $items = [];

        for( $x = max( $from, 0 ); $x < min( $to, 6 ); $x++ ){
            $dayOfTheWeekResource = $this->getItemResource($x);
            $items[] = $dayOfTheWeekResource->get();
        }

        return [ $items, count($items) ];
    }

    public function createItemResource($resourceIdentifier)
    {
        return new DayOfTheWeek($resourceIdentifier);
    }
}
```

We can now change our url handler to use our collection resource instead of the item resource.

``` php
$this->addUrlHandlers(
[
    "/days-of-the-week" => new RestCollectionHandler( '\MyAPI\Resources\DaysOfTheWeek' )
] );
```

Now we can request either `/days-of-the-week` to get a list of the days or `/days-of-the-week/1` to get a
specific one. Here's what the collection looks like:

``` javascript

{
    "_href": "\/days-of-the-week",
    "items": [
        {
            "_href": "\/days-of-the-week\/0",
            "Day": "Mon"
        },
        {
            "_href": "\/days-of-the-week\/1",
            "_id": 1,
            "Day": "Tue"
        },
        {
            "_href": "\/days-of-the-week\/2",
            "_id": 2,
            "Day": "Wed"
        },
        {
            "_href": "\/days-of-the-week\/3",
            "_id": 3,
            "Day": "Thu"
        },
        {
            "_href": "\/days-of-the-week\/4",
            "_id": 4,
            "Day": "Fri"
        },
        {
            "_href": "\/days-of-the-week\/5",
            "_id": 5,
            "Day": "Sat"
        }
    ],
    "count": 6,
    "range": {
        "from": 0,
        "to": 5
    }
}
```