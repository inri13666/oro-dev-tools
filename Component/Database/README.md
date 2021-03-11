# Gorgo Database Component

### Limitations
- Database user should have rights for DROP & CREATE DATABASE
- PHP environment should allow to use "putenv" function

### Installation

```
    composer require gorgo13/database-component
```

### Known issues

- PostgerSQL may fail sometimes with following message:
```
  [Symfony\Component\Process\Exception\ProcessFailedException]
  The command "dropdb --if-exists -U postgres -h 127.0.0.1 -p 5432 acme_db" failed.
  Exit Code: 1(General error)
  Working directory: /var/home/sites/acme-site
  Output:
  ================
  Error Output:
  ================
  dropdb: database removal failed: ERROR:  database "acme_db" is being accessed by other users
  DETAIL:  There are 1 other sessions using the database.
```
OR
```
  [Symfony\Component\Process\Exception\ProcessFailedException]
  The command "createdb -U postgres -h 127.0.0.1 -p 5432 -O postgres -T acme_db backup_acme_db_8ba0bdc20ffb74f130ec21d2d1d737fe" failed.
  Exit Code: 1(General error)
  Working directory: /var/home/sites/acme-site
  Output:
  ================
  Error Output:
  ================
  createdb: database creation failed: ERROR:  source database "acme_db" is being accessed by other users
  DETAIL:  There is 1 other session using the database.
```
