test_coverage_dir=coverage
tests_dir=tests

# PHONY is used to specify targets that must run regardless of whether
# a file with the target name already exists.
.PHONY: clean style test

clean: clean-test
clean-test:
	rm -rf ${test_coverage_dir}

tests: test
test: clean-test
	mkdir ${test_coverage_dir}
	phpunit --coverage-html="${test_coverage_dir}/html" --testdox-html="${test_coverage_dir}/dox.html" ${tests_dir}

style:
	phpcs --standard=PEAR .
