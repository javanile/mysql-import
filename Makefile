#!make

readme-standard:
	@curl -s https://www.javanile.org/readme-standard/checker.sh?t=$$(date +%s) | bash