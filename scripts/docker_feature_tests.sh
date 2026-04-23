#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "[1/6] Resetting Docker stack (fresh DB init)..."
docker compose down -v --remove-orphans

echo "[2/6] Building and starting containers..."
docker compose up -d --build

echo "[3/6] Waiting for database readiness..."
for i in {1..60}; do
  if docker compose exec -T web php -r '$c=@new mysqli("db","root","mysql","Hospital_3NF"); if($c && !$c->connect_error){echo "ok"; exit(0);} exit(1);' >/dev/null 2>&1; then
    echo "Database is ready for app connections."
    break
  fi
  sleep 2
  if [[ "$i" -eq 60 ]]; then
    echo "Database did not become ready in time."
    exit 1
  fi
done

echo "[4/6] Running SQL feature checks..."
docker compose exec -T web php scripts/run_sql_checks.php tests/sql/feature_expansion_checks.sql

echo "[5/6] Verifying web endpoint..."
HTTP_CODE="$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000/")"
echo "HTTP status for /: ${HTTP_CODE}"
if [[ "$HTTP_CODE" != "200" ]]; then
  echo "Web container health check failed (expected 200)."
  exit 1
fi

echo "[6/6] Container status summary..."
docker compose ps

echo "All docker feature checks completed."
