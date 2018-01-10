<?php
namespace Arillo\InstagramScraper\CMS;

use \Convert;
use \GridFieldBulkActionHandler;
use \SS_HTTPRequest;
use \SS_HTTPResponse;

/**
 * De-/activate bulk action handlers.
 *
 * @package instagram-scraper
 * @author bumbus <sf@arillo.net>
 */
class InstagramRecordBulkHandler extends GridFieldBulkActionHandler
{
    /**
     * RequestHandler allowed actions
     * @var array
     */
    private static
        $allowed_actions = [
            'activate',
            'deactivate',
        ],

        $url_handlers = [
            'activate' => 'activate',
            'deactivate' => 'deactivate',
        ]
    ;

    /**
     * Delete the selected records passed from the delete bulk action
     *
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse List of deleted records ID
     */
    public function activate(SS_HTTPRequest $request)
    {
        $ids = [];

        foreach ($this->getRecords() as $record)
        {
            array_push($ids, $record->ID);
            $record->Hidden = false;
            $record->write();
        }

        $response = new SS_HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');
        return $response;
    }
    /**
     * Delete the selected records passed from the delete bulk action
     *
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse List of deleted records ID
     */
    public function deactivate(SS_HTTPRequest $request)
    {
        $ids = [];

        foreach ($this->getRecords() as $record)
        {
            array_push($ids, $record->ID);
            $record->Hidden = true;
            $record->write();
        }

        $response = new SS_HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');
        return $response;
    }
}
