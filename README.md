## Install And Usage

### 1) Launch the project via Docker
```
docker-compose up -d
```

### 2) Copy .env.example into .env

### 3) Run migrations (from weather-aggregator-php-1 container or locally)
```
php artisan migrate
```
And choose Yes, when it will ask to create DB.

### 4) To see the API documentation please run
```
php artisan l5-swagger:generate
```
And then you should see the documentation here:
http://localhost/api/documentation

### 5) In order to launch weather collection manually you can run
```
php artisan job:launch-get-weather-job
```

Note: In order to it works automatically on the server with Laravel there should be written in cron:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 6) You can find phpMyAdmin here if needed
http://localhost:8080/
Since it is a demo work, I used root user and password:
host: mysql, user: root, password: root

### 7) To run unit tests:
```
php ./vendor/bin/phpunit
```

### 8) In order to add location please use POST /api/add-location endpont (see swagger documentation for how to use it)

### 9) In order to get the average temperature please use GET /api/get-average-weather endpoint (see swagger documentation for how to use it)

Note: It caches the data for the previous days with the same fromTime and toTime.
      It does not cache date if the toTime is in the future, because the data for it may be updated by agregator.
      The cache timeout is set to 1 hour.

