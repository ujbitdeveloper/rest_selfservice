# CI4 JWT REST API (Docker + PostgreSQL)

    docker compose up -d --build

- API      : http://localhost:7701
- Postgres : localhost:3308 (db ci4_api, user ci4_user, pass ci4_pass)
- Demo user: admin / admin123

## Endpoint
    curl -X POST localhost:7701/api/token   -H 'Content-Type: application/json' -d '{"username":"admin","password":"admin123"}'
    curl -X POST localhost:7701/api/refresh -H 'Content-Type: application/json' -d '{"refresh_token":"<REFRESH>"}'
    curl -X POST localhost:7701/api/data    -H 'Content-Type: application/json' -H 'Authorization: Bearer <ACCESS>' -d @device.json

## Migrasi DB yang sudah jalan
    docker exec -i ci4_db psql -U ci4_user -d ci4_api < db/migrate_devices.sql
