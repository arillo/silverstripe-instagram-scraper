<?php
namespace Arillo\InstagramScraper\CMS;

use \LeftAndMainExtension as SS_LeftAndMainExtension;
use \Requirements;

/**
 * Initialize js.
 *
 * @package instagram-scraper
 * @author bumbus <sf@arillo.net>
 */
class LeftAndMainExtension extends SS_LeftAndMainExtension
{
    public function init()
    {
        Requirements::javascript(
            INSTAGRAM_SCRAPER_MODULE_DIR . '/javascript/InstagramRecordBulkHandler.js'
        );
    }
}
