#Installation
Deploy Curse folder to the extension folder of the MediaWiki installation.

Inside extensions.php add the following line:
     require_once("$IP/extensions/Curse/HydraCore.php");

Inside LocalSettings.php, add the following options:
     $bodyName = '<url>'; -- This is for the adtag body class.  Please use the sitename web address (example: www.bl2wiki.com would be $bodyName='bl2wiki';)
     $disclaimer = '<text>'; - Please put the disclaimer for the website in this variable. If there is no information in this variable, it will default to a standard message.
     $fyear =<integer>; - Input the founding variable here, if there is nothing set, it will default to 2006.

Main Page Title Change
     One of the extensions will change just the title on the main page.  In order to do this, please edit the Pageview-view-mainpage.

#Cron Job
	0 * * * * php ~/public_html/maintenance/generateSitemap.php --fspath=../sitemaps/  --urlpath http://www.falloutwiki/sitemaps --server http://www.falloutwiki.com > /dev/null 2>&1