.PHONY: test test-with-coverage

test:
	vendor/bin/phpunit


test-with-coverage:
	vendor/bin/phpunit \
		--coverage-html coverage_html
