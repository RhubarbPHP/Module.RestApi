Basics of REST API design in Rhubarb
====================================

To create a REST resource you need to extend the `RestResource` class. A RestResource object is the most basic
type of REST object. It knows that it needs an href property and provides a pattern for dealing with
GET, POST, PUT, HEAD and DELETE verbs.

When you extend RestResource you must create a getRelativeUrl() method which should return the relative URL for this
resource (depending on how this resource is served the full "href" property maybe different)

~~~ php
class WeatherResource extends RestResource
{

}
~~~

Now we need to get our resource to response to http verbs. Let's implement GET:

~~~ php
class WeatherResource extends RestResource
{
    public function get(RestHandler $handler = null)
    {
        // Start with the 'skeleton'. This gives us a stdClass object with the href already populated.
        $resource = $this->getSkeleton($handler);

        $resource->Outlook = "cloudy";
        $resource->MaxTemp = 22;
        $resource->MinTemp = 7;

        return $resource;
    }
}
~~~

REST resources are retrieved using URLs and so are made visible in Rhubarb using Urlhandler objects like
all other URLs. Edit your app.config.php and register some the handler:

~~~ php
$this->addUrlHandlers(
[
    "/weather" => new RestResourceHandler( '\MyAPI\Resources\WeatherResource' )
] );
~~~

Requesting the resource in the browser should now give you the following output:

~~~ javascript
{
    _href: "/weather",
    Outlook: "cloudy",
    MaxTemp: 22,
    MinTemp: 7
}
~~~

## Item resources

If you need to represent a resource that has a unique identifier, you should extend `ItemRestResource` instead.
This class adds one special property to the output "_id" which is can be used in passing to other requests etc.

~~~ php
class DayOfTheWeek extends ItemRestResource
{
    public function get(RestHandler $handler = null)
    {
        // Start with the 'skeleton'. This gives us a stdClass object with the href already populated.
        $resource = $this->getSkeleton($handler);

        // Silly example but just switch on the ID and return the correct day of the week.
        $days = [ "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun" ];

        // $this->id contains the identifier.
        $resource->Day = $days[ $this->id ];

        return $resource;
    }
}
~~~

In order to serve an item resource you must however use a RestCollectionHandler. This handler knows to expect
and ID on the URL and passes it to the resource (thereby ending up in $this->id).

~~~ php
$this->addUrlHandlers(
[
    "/day-of-the-week" => new RestCollectionHandler( '\MyAPI\Resources\DayOfTheWeek' )
] );
~~~

Requesting /day-of-the-week/1 in the browser should now give you the following output:

~~~ javascript
{
    _href: "/day-of-the-week",
    _id: "1",
    Day: "Tue"
}
~~~

## Collection resources

To present a list of 