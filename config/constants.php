<?php
/**
 * Constants representing parser run mode:
 * - dry: no database updates
 * - normal: with updates
 */
define('RUN_MODE_NORMAL', 1);
define('RUN_MODE_DRY', 2);

/**
 * Constants representing the status of entries in a temporary table.
 */
define('ENTRY_STATUS_UNKNOWN', 0);
define('ENTRY_STATUS_REJECTED', 1);
define('ENTRY_STATUS_PARSE_BAD_ID', 100);
define('ENTRY_STATUS_PARSE_ERROR', 101);
define('ENTRY_STATUS_ID_DUPLICATE', 102);
define('ENTRY_STATUS_CARDNUMBER_DUPLICATE', 103);
define('ENTRY_STATUS_CARDNUMBER_ALREADY_TAKEN', 104);
define('ENTRY_STATUS_TO_ADD', 2);
define('ENTRY_STATUS_TO_UPDATE', 3);
define('ENTRY_STATUS_TO_RESTORE', 4);
define('ENTRY_STATUS_TO_DELETE', 5);
define('ENTRY_STATUS_NOT_CHANGED', 6);

/**
 * Constants representing report entries statuses.
 */
define('REPORT_STATUS_INFO', 1);
define('REPORT_STATUS_ERROR', 2);

/**
 * The names of the tables where parser temporary data is stored.
 */
define('WORK_TABLE_NAME', 'entries');
define('STATUS_TABLE_NAME', 'entry_statuses');
