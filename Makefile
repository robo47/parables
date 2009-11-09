BUILD_PATH=/tmp/parables

check:
	@find Parables -type f -name "*php" -exec php -l {} \;

build:
	@if [ -d $(BUILD_PATH) ]; then rm -rf $(BUILD_PATH); fi;
	@git archive --format=tar --prefix=parables/ HEAD | (cd /tmp/ && tar xf -)
