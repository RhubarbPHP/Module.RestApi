Building RESTful APIs
=====================

An API is any public interface to a documented library of functions. APIs are easier to work with if they work from
a standard and so on the web there are a handful of very common API specifications SOAP, and REST being two of the
most common. REST is increasingly popular not just because it is easy to understand but also because it aligns more
closely with the nature of HTTP.

Understanding the HTTP protocol is the key to designing great REST APIs because the two are so closely bound. A
very useful guide in getting started with REST API design is
[Janseen Geert's introduction](http://restful-api-design.readthedocs.org/en/latest/intro.html).

HTTP is a protocol for getting, creating, updating and deleting resources - usually HTML documents and images but it
can be used for any type of resource. The most important concept to embrace with REST API design is to translate
all of your objects and actions into the language of resources and collections.

For many of objects you want to expose in your API this is an easy exercise. For example if you have Contact
model in your application you might want a Contact resource in your API. Same thing for SalesOrder, BlogPost,
Product, Comment models. Each of these resources will be associated with URLs like:

URL           |Meaning
--------------|----------------------------------------
/contacts     |A collection resource listing contacts
/contacts/{id}|A single contact item resource
/posts        |A collection resource listing blog posts
/posts/{id}   |A single blog post item resource

Some transactions with your API require a little bit of creative thinking in order to mould them into the
resource paradigm. Mostly these types of transactions are 'actions' you need to take on those more traditional
resources.

Let's imagine you had a SalesOrder resource, and you API needs to support the ability to 'dispatch' the order
with a courier. We also want to be able to track the progress of that dispatch. Traditionally we might think of
the dispatching to be an action on the SalesOrder resource. Likewise the polling for status updates on the
dispatch would be seen as a completely different endpoint where you pass some sort of ID and get status data
back. It's no co-incidence that we all thing this way - it is after all how it's very likely how the application
works on the server. In this mode of thinking you might take the sales order API URL and add an action end point
on the end:

```
POST/(PUT?) /sales-orders/123456/dispatch
```

And for polling you might have an end point like

```
GET /dispatch-status/5432212
```

You might notice a problem - I can't decide whether to use POST or PUT for the dispatch end point.
That's because the approach of triggering an action on a resource has no parallel in HTTP. This is the sort
of problem symptomatic of building REST APIs using a traditional RPC mindset.

In REST API design you have the opportunity to express this transaction as a resource. A more RESTful
API would handle this transaction like this:

```
POST { "SalesOrderID": 123456 } to /dispatches
this returns a "Dispatch" resource
{
  "_id": 5432212,
  "_href": "/dispatches/5432212",
  "SalesOrderID": 123456,
  "Status": "Awaiting Courier"
}
```

To poll:

```
GET /dispatches/5432212
this returns the same "Dispatch" resource
{
  "_id": 5432212,
  "_href": "/dispatches/5432212",
  "SalesOrderID": 123456,
  "Status": "In Transit"
}
```

This example highlights another facet of great REST APIs - you don't need a manual in order to program against them.
This is a natural consequence of building your API with a resource based mindset as all resources should have a
"href" to allow them to be fetched again. After we POST to the dispatches end point it returns a Dispatch resource.
That resource contains the href to the dispatch. The developer now believes that this dispatch resource is a
permanent resource they can re-fetch simply by requesting that href again (as we do in our polling operation).

Building REST APIs with the REST API module in Rhubarb
------------------------------------------------------

[Creating basic REST resources](basics)
:	Learn how to create custom REST resources and respond to get, post, put and delete

[Model bound REST resources](model-bound)
:	If you are using Stem modelling you can get REST resources up in minutes.

[Authentication](authentication)
:	Learn how to handle authentication with APIs

[Consuming REST APIs](clients)
:	Consume other Rhubarb APIs using the REST client classes.
