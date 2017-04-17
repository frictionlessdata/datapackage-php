# Contributing

The project follows the [Open Knowledge International coding standards](https://github.com/okfn/coding-standards).


## Getting Started

1. Clone the repo
2. Run the tests
```
$ composer install
$ composer test
```

## Phpunit - for unit tests

Phpunit is used for unit tests, you can find the tests under tests directory

Running Phpunit directly: `vendor/bin/phunit`

## Coveralls - for coverage

when running `composer test` phpunit generates coverage report in coverage-clover.xml - this is then sent to Coveralls via Travis.
