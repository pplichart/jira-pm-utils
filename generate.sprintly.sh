#!/bin/bash
FILE="sprintgenerated.flag"

if [ -f "$FILE" ];
then
	rm "$FILE"
else
	php reportCustomer.php
	php reportInternal.php
	#php healthCheck.php
	cp reports/* ../../public/reports/md/ -R
	touch "$FILE"
fi
