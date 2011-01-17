coverage_dir=coverage
dox_dir=dox
tests_dir=tests

# PHONY is used to specify targets that must run regardless of whether
# a file with the target name already exists.
.PHONY: clean style test

clean: clean-test
clean-test:
	rm -rf ${coverage_dir}
	rm -rf ${dox_dir}

test: clean-test
	mkdir ${coverage_dir}
	mkdir ${dox_dir}
	phpunit --coverage-html="${coverage_dir}" --testdox-html="${dox_dir}/dox.html" ${tests_dir} 

style:
	phpcs --standard=PEAR .
