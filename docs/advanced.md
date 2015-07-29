Advanced Concepts
=================

## Canonical URLs

A REST API is composed of many different resource objects. Each of those resources is accessed using URLs some of 
which are contextual, for example the "Organisation" resource in this URL is contextual in that it depends which
contact you've selected as to which organisation you get.

```
/contacts/3/organisation
```

However some resources should have a URL by which it is permanently reachable regardless of the status of
other resources. In our example there is no reason to assume that the organisation for contact 3 is
automatically invalid if contact 3 itself is deleted and we would assume there will be other contacts attached
to the same organisation.

In this case an Organisation should have a "canonical URL" - a URL that permanently represents the resource. In
our example the canonical URL might be:

```
/organisations/1
```

When a resource has a canonical URL it will appear in the "_href" property of all item resources requested for
that resource type, including [nested and linked resources](model-bound#nested-resources).

### Setting canonical URLs

The direct way to set a canonical URL is to simply override the `getHref` function on your RestResource
object. However for combined collection/item resource objects (i.e. all ModelRestResource objects) you need
to inspect whether the current context is the collection or an item:

``` php
class OrganisationResource extends ModelRestResource
{
    protected function getHref()
    {
        if ( $this->model ){
            return "/organisations/".$this->model->UniqueIdentifier;
        } else {
            return "/organisations";
        }
    }
}
```

A more straightforward approach however is to use a `RestApiRootHandler` `UrlHandler` object as a parent
for all of your top level resource end points. Child urls of this end point are automatically recognised
as the canonical urls for those resources.

## Child Resources

Let's say you want to support a sub collection filtere by a parent resource, for example

```
/organisation/1/contacts
```

i.e. fetch all the contacts linked to organisation number 1

To support this you need to override the `getChildResource` function in the resource object that represents
`/organisations/1`.

```php
class OrganisationResource extends ModelRestResource
{
    public function getChildResource($childUrlFragment)
    {
        switch( $childUrlFragment ){
            case "/contacts":
                // Get the collection of contacts for the organisation
                $organisation = $this->getModel();
                $collection = $organisation->getContacts();

                // Create the contact resource object and assign the correct
                // collection.
                $contactResource = new ContactResource($this);
                $contactResource->setModelCollection($collection);

                return $contactResource;
                break;
        }

        return parent::getChildResource($childUrlFragment);
    }
}
```

This function will receive the remaining part of the URL that hasn't been processed from which you can
decide what type of child resource needs returned.