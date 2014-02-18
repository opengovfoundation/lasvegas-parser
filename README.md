lasvegas-parser
===============

#Las Vegas Municode Parser

Translates Municode XML into State Decoded XML

##Code Files:

* Connection.php: its being used to establish the database connection
* simple_html_dom.php: Class to parse every item in the web-page.
* super_crawl: Main parser who interact with the database, bring urls to parse, parse them and create .xml files
* trim_spaces.php: crawl the files directory and trim the spaces in every file (Sorry i made this as a patch script, because at that time all the files were already created and i dont want to crawl them again)


##Directories:

* Files: we are storing xml files in this directory
