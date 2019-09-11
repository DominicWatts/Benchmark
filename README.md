# Benchmark #

Benchmark shell script to test timings on actions.

# Install instructions #

`composer require dominicwatts/benchmark`

`php bin/magento setup:upgrade`

`php bin/magento setup:di:compile`

# Usage instructions #

`xigen:benchmark:stock [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:stock run`

`php bin/magento xigen:benchmark:stock run -l 10`

`xigen:benchmark:product [-l|--limit [LIMIT]] [--] <run>`

`php bin/magento xigen:benchmark:product run`

`php bin/magento xigen:benchmark:product run -l 10`