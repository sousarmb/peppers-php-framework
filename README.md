# Peppers Framework

## What is it?
Peppers is a Web MVC PHP framework designed for the developer who focuses on separation of concerns first, building small units of work which can be pipelined together to provide a response to a request. It tries to be fully customizable, allowing the developer to create its own code for core tasks.

## What can it do?
* Abstract & Concrete Object Factory 
* Content Negotiation 
* Credential Management For Multiple Datasources 
* Database Access (PDO, MySQL only) 
* Dependency Injection 
* Events 
* Input Validation 
* Local Files 
* Logging
* Model Repositories (SQL only) 
* Multiple Database access
* ORM 
* Promises (SQL only) 
* Routing 
* Services 
* Views 

## What it's not?
Peppers is not a full blown Web framework like Symphony and it's not particulary fast either.

## What can't it do?
* Assets
* Authentication & Authorization 
* Connection Pooling
* Cookies 
* Full REST (no PUT or PATCH support yet) 
* Internationalization 
* Offline Jobs 
* Remote Caching (Memcached, Redis, etc)
* Remote Files 
* Sessions 
* Translations 
* Users 
* Websockets 

## How does this work?
In a nutshell, the Kernel takes care of booting the framework and application and shutting it down after a response has been sent to the client.

### Booting
It starts the necessary configuration files exist and its content is valid. Then loads the loaders for the services, routing, event handling and/or any custom code by the developer.

### Request & Response Processing
The Kernel gets the route asserted by the Router. Then it starts a pipeline that gets a request handler instance from the Factory, processes the request, processes the data returned from the handler and sends a response back to the client, following the standard content negotiation rules. The end of the pipeline run signals the Kernel that a response has been sent back to the client.

### Shutting It Down
The Kernel then signals the ServiceLocator to signal registered services to run any shutdown routine they might have. This includes the EventStore service; makes it process any deferred event that might have been registered.

## Bugs
Most likely, please report them on Github.

## Comments and sugestions
Send to peppers.php.framework@gmail.com

Thank you :)
