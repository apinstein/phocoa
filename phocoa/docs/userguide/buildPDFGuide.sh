#!/bin/sh
# tools:
# fink install docbook-xsl fop
fop -xml PHOCOA\ User\ Guide.xml -xsl /sw/share/xml/xsl/docbook-xsl/fo/docbook.xsl -pdf "PHOCOA User Guide.pdf"
# old way
# xsltproc file:///sw/share/xml/xsl/docbook-xsl/fo/docbook.xsl PHOCOA\ User\ Guide.xml > out.fo
# fop out.fo "PHOCOA User Guide.pdf"
# rm out.fo
