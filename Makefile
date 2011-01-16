# PHONY is used to specify targets that must run regardless of whether
# a file with the target name already exists.
.PHONY: test

test:
	phpunit --coverage-html="coverage" --testdox-html="dox.html" tests
