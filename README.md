# Benchmark #

Benchmark shell script to test timings on actions.

![phpcs](https://github.com/DominicWatts/Benchmark/workflows/phpcs/badge.svg)

![PHPCompatibility](https://github.com/DominicWatts/Benchmark/workflows/PHPCompatibility/badge.svg)

![PHPStan](https://github.com/DominicWatts/Benchmark/workflows/PHPStan/badge.svg)

![php-cs-fixer](https://github.com/DominicWatts/Benchmark/workflows/php-cs-fixer/badge.svg)

# Install instructions #

`composer require dominicwatts/benchmark`

`php bin/magento setup:upgrade`

`php bin/magento setup:di:compile`

# Usage instructions #

Stock update

`xigen:benchmark:stock [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:stock run`

`php bin/magento xigen:benchmark:stock run -l 10`

Product Status Update

`xigen:benchmark:status [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:status run`

`php bin/magento xigen:benchmark:status run -l 10`

Product Price Update

`xigen:benchmark:price [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:price run`

`php bin/magento xigen:benchmark:price run -l 10`

Customer Vat Number Update

`xigen:benchmark:customer [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:customer run`

`php bin/magento xigen:benchmark:customer run -l 10`

Category Keywords Update

`xigen:benchmark:category [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:category run`

`php bin/magento xigen:benchmark:category run -l 10`