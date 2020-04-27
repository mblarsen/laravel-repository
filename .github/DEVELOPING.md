# Developing

Please first read [CONTRIBUTING](.github/CONTRIBUTING.md).

## Running tests

```bash
composer run-script test
```

You can run only some tests using filter:

```bash
composer run-script test -- --filter="fetches_"
```

Now all tests starting with `fetches_` are run.

## Run tests in MySQL

By default the tests run in SQLite memory. However, to run all tests full
database is needed.

See [TestCase](tests/TestCase.php) how this is set up.

To easily run tests in docker setup MySQL:

```bash
docker run --name laravel-repository-mysql --detach -e MYSQL_ALLOW_EMPTY_PASSWORD=true -p 3306:3306 -d mysql:5.7
docker exec laravel-repository-mysql mysql -uroot -e "create database laravel_repository;"

# then

CI=true composer run-script test
```
