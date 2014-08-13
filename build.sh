#!/bin/bash

DASHICONS_CSS='../../../wp-includes/css/dashicons.css'
DASHICONS_DATAFILE='js/dashicons.json'

outer_pattern='\.dashicons-([a-z0-9\-]+):'
result=$(grep -ioE -e "$outer_pattern" "$DASHICONS_CSS")

#echo ${$result/.*/hump}
echo $result
echo $result | sed -e 's/\.dashicons-before: //g' -e 's/\.dashicons-/"/g' -e 's/:/"/g' -e 's/" "/","/g' -e 's/^/[/g' -e 's/$/]/g' > $DASHICONS_DATAFILE
