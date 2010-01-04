#!/bin/sh

# tools:
# fink install docbook-xsl fop
DOCBOOK_INPUT='PHOCOA_User_Guide.xml'
if [ -z $1 ]; then
    DOCBOOK_OUTPUT=.
else
    DOCBOOK_OUTPUT=$1
fi
echo $DOCBOOK_OUTPUT

# PDF Version
fop -xml $DOCBOOK_INPUT -xsl /sw/share/xml/xsl/docbook-xsl/fo/docbook.xsl -pdf "$DOCBOOK_OUTPUT/PHOCOA_User_Guide.pdf"

# HTML Version
docbook2html -o $DOCBOOK_OUTPUT/userguide-html "$DOCBOOK_INPUT"
