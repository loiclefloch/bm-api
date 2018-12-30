# BookmarkManager API

A Symfony project created on September 8, 2015, 7:27 pm.

# Docker

## Import db

docker exec -i \$(docker-compose ps -q db) mysql -uroot -p"root" bmapi_dev < ~/Downloads/bmapi_dev.sql

# Project Documentation

## Run the tests

`phpunit -c app/`

# API Documentation

The API follows common REST conventions. This means you should use GET requests to retrieve data and POST, PUT, or PATCH requests to modify data. All API requests must be done over HTTPS.

Every response will be a JSON object, which is a key-value hash. Data for a resource will be returned in the key named data. Errors will be returned in the key named errors or error.

HTTP response codes indicate the status of your request:

200 - Ok: The request has succeeded.
201 - Created: The request has been fulfilled and resulted in a new resource being created.
202 - Accepted: The request has been accepted for processing, but the processing has not been completed. The stats resource may return this code.
400 - Bad Request: The request is invalid. Check error message and try again.
401 - Unauthorized: The request requires authentication, or your authentication was invalid.
403 - Forbidden: You are authenticated, but do not have permission to access the resource.
404 - Not Found: The resource does not exist.
50x - Server Error: Service unavailable, try again later.

## Authentication

We use oauth2.

### Generate an oauth client (Server side)

`php app/console bm:oauth-server:client:create \ --grant-type="authorization_code"\ --grant-type="password" --grant-type="refresh_token"\ --grant-type="token" --grant-type="client_credentials"`

### Get an anonymous token (Client side)

The _grant_type_ is 'client_credentials', because we want an access_token for an anonymous user.
See the oauth2 RFC for further informations.

`http --form POST http://apidev.bm.fr/app_dev.php/oauth/v2/token \ 'Content-Type':'application/x-www-form-urlencoded' \ 'client_id'='[client_id]' \ 'client_secret'='[client_secret]' \ 'grant_type'='client_credentials'`
  
This request will generate a temporary access_token for public user.

Use it to access to all routes of the API in IS_AUTHENTICATED_ANONYMOUSLY role.

### Use the access_token

The access_token must be use on the Authorization header.

`http GET http://www.local.bm/app_dev.php/api/users/me \ 'Authorization':'Bearer Y2FlZTYwYmE0YjIwMmEyNWM0ZDYwZmM0YzFmMTJiYjQwMDMwZjg5MzNiZDczMWM5M2VkMGY5ZDM4ZjI1OTRkNw'`

### Api response for oauth2

For oauth2, the errors and responses must follow the following RFC :

- [http://tools.ietf.org/html/rfc6749#section-5.2](http://tools.ietf.org/html/rfc6749#section-5.2)
- [http://tools.ietf.org/html/rfc6749#section-5.1](http://tools.ietf.org/html/rfc6749#section-5.1)

## Requests

### Create a new user

Create a user require the IS_AUTHENTICATED_ANONYMOUSLY role. First, you must get an anonymous token.
After that you can send the POST request using the access_token

```
    http --json POST http://www.local.bm/app_dev.php/api/users \
    'Authorization':'Bearer [access_token]' \
    'Content-Type':'application/json' \
    email="test6@test.fr" \
    password="bonjour1" \
    last_name="test" \
    gender:=0 \
    first_name="test"
```

## Get the user access_token

The requests that needs an authenticate user require an access_token. This access_token is the key that allows the API
to recognize the request owner.

You can't send the token as url query parameter for obvious security reasons.

The scheme is _/oauth/v2/token_.

The _grant_type_ is 'password', because we want an access_token by providing the user's credential

```
    http --form POST http://www.local.bm/app_dev.php/oauth/v2/token \
    'Content-Type':'application/x-www-form-urlencoded' \
    'username'='test3@test.fr' \
    'password'='bonjour1' \
    'client_id'='[client_id]' \
    'client_secret'='[client_secret]' \
    'grant_type'='password'
```
