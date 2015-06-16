# TYPO3-CMS extension Retrostats

This extension copies the exact functionality for writing the Apache-style log files as used to be part of the core until v4.7.
The only issue to take care of is to add the [FE][logfile_dir] setting in AdditionalConfiguration.php instead of LocalConfiguration because the Install tool will remove it otherwise.

All TypoScript settings should work as they used to do.

Development versions can be found in this github repository. Released versions of the extension are here:
http://typo3.org/extensions/repository/view/retrostats

## Configuration

The ['TYPO3_CONV_VARS']['FE']['logfile_dir'] setting should be placed in AdditionalConfiguration.php instead of LocalConfiguration.php, because the Install tool will remove it as it's no longer a known TYPO3 setting.

## TypoScript configuration
	
| Property: | Data type: | Description: | Default: |
|-----------|------------|--------------|----------|
| stat | boolean | Enable stat logging at all. | true |
| stat_typeNumList | int/list | List of pagetypes that should be registered in the statistics table, sys_stat. If no types are listed, all types are logged. Default is "0,1" which normally logs all hits on framesets and hits on content keeping pages. Of course this depends on the template design. | 0,1 |
| stat_excludeBEuserHits | boolean | If set a page hit is not logged if a user is logged in into TYPO3. | false |
| stat_excludeIPList | list of strings | If the REMOTE_ADDR is in the list of IP-addresses, it's also not logged. Can use wildcard, e.g. "192.168.1.*" |
| stat_mysql | boolean | Enable logging to the MySQL table sys_stat. | false |
| stat_apache | boolean | Enable logging to the log fle "stat_apache_logfle" | false |
| stat_apache_logfile | filename | This defines the name of the log file where TYPO3 writes an Apache-style logfile to. The location of the directory is defined by $TYPO3_CONF_VARS['FE']['logfile_dir'] which must exist and be writable. It can be relative (to PATH_site) or absolute, but in any case it must be within the regular allowed paths of TYPO3 (meaning for absolute paths that it must be within the "lockRootPath" set up in $TYPO3_CONF_VARS). It is also possible to use date markers in the filename as they are provided by the PHP function strftime(). This will enable a natural rotation of the log files. Example: config.stat_apache_logfile = typo3_%Y%m%d.log This will create daily log fles (e.g. typo3_20060321.log). |
| stat_apache_pagenames | string | The "pagename" simulated for apache. Default: "[path][title]--[uid].html" Codes: [title] = inserts title, no special characters and shortened to 30 chars.; [uid] = the id; [alias] = any alias; [type] = the type (typeNum); [path] = the path of the page; [request_uri] = inserts the REQUEST_URI server value (useful with RealUrl for example) |
| stat_apache_notExtended | boolean | If true the log fle is NOT written in Apache extended format |
| stat_apache_noHost | boolean | If true the HTTP_HOST is - if available - NOT inserted instead of the IP-address |
| stat_apache_niceTitle | boolean/string | If set, the URL will be transliterated from the renderCharset to ASCII (e.g ä => a, à => a, &#945; "alpha" => a), which yields nice and readable page titles in the log. All non-ASCII characters that cannot be converted will be changed to underscores. If set to "utf-8", the page title will be converted to UTF-8 which results in even more readable titles, if your log analyzing software supports it. |
| stat_apache_noRoot | boolean | If set, the root part (level 0) of the path will be removed from the path. This makes a shorter name in case you have only a redundant part like "home" or "my site". |
| stat_titleLen | int 1-100 | The length of the page names in the path written to log file/database | 20 |
| stat_pageLen | int 1-100 | The length of the page name (at the end of the path) written to the log fle/database. | 30 |
| stat_IP_anonymize | boolean | (Since TYPO3 4.7) Set to 1 to activate anonymized logging. Setting this to 1 will log an empty hostname and will enable anonymization of IP addresses. | 0 |
| stat_IP_anonymize_mask_ipv4 | int | (Since TYPO3 4.7); Prefx-mask 0..32 to use for anonymisation of IP addresses (IPv4). Only used, if stat_IP_anonymize is set to 1.; Recommendation for Germany: config.stat_IP_anonymize_ipv4 = 24 | 24 |
| stat_IP_anonymize_mask_ipv6 | int | (Since TYPO3 4.7); Prefx-mask 0..128 to use for anonymisation of IP addresses (IPv6). Only used, if stat_IP_anonymize is set to 1. Recommendation for Germany: config.stat_IP_anonymize_ipv6 = 64 | 64 |
| stat_logUser | boolean | (Since TYPO3 4.7) Confgure whether to log the username of the Frontend user, if the user is logged in in the FE currently. Setting this to 0 allows to anonymize the username. | 1 |

