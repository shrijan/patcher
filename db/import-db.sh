#!/bin/bash
if ! mysql -e 'SELECT * FROM users LIMIT 1;' db 2>/dev/null; then
  echo 'Importing the database'
  gzip -dc /var/www/html/db/koala-db.sql.gz | mysql db
fi
