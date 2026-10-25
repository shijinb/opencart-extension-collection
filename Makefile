.PHONY: install test build clean

install:
	composer install

test:
	bash tests/run.sh

build:
	bash scripts/build.sh

clean:
	rm -rf dist .cache vendor
