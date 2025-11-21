# Time Tracker

API and webapplication for user time tracking purposes.

## Docker

Start containers:

 ```bash
 bash start.sh
 ```

 Make migrations on service `app`:

 For example fron contianer bash:

 ```bash
php bin/console doctrine:migrations:migrate
 ```

 or host:

 ```bash
 docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
 ```


Open browser `http://localhost:8000/` - this is API base url.
Open browser `http://localhost:8001/` - this is Frontend base url.


Alternatively use Swagger `http://localhost:8000/api/doc`


# TODO

Done - Add Nelmio and Swagger support
Done - Add user description for reported work
Add Api DTO for request params mapping
Done - Add Keycloak for user identity management and more flexible time report for users eg. from mobile app.

 