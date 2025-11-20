# Time Tracker

API and webapplication from user time tracking purposes.

## Docker

Start containers:

 ```bash
 docker compose up -d
 ```

 Make migrations:

 ```bash
php bin/console doctrine:migrations:migrate
 ```

Open browser `http://localhost:8000/` - this is API base url.
Open browser `http://localhost:8001/` - this is Frontend base url.


## API Endpoints

Use curl for example:

```
curl -X POST http://localhost:8000/api/employees -H "Content-Type: application/json" -d '{"firstName":"Karol","lastName":"Szabat"}'

curl -X POST http://localhost:8000/api/worktimes -H "Content-Type: application/json" -d '{"employeeId":"<uuid>","startAt":"2025-11-01T08:00:00+01:00","endAt":"2025-11-01 16:00:00+01:00"}'

curl 'http://localhost:8000/api/summary?employeeId=<uuid>&date=2025-11-01'

```

You may use `End-to-End Tests.postman_collection.json`. Import this file in Postman app.

 