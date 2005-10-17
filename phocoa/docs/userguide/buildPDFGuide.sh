xsltproc file:///sw/share/xml/xsl/docbook-xsl/fo/docbook.xsl PHOCOA\ User\ Guide.xml > out.fo
java org.apache.fop.apps.Fop out.fo "PHOCOA User Guide.pdf"
rm out.fo
