<?php
namespace Arillo\InstagramScraper\Tasks;

use Arillo\InstagramScraper\Crawler;
use \BuildTask;
use \InstagramRecord;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Task supposed to run in a cronjob.
 * Needs params type (for now only hashtag is supported) & subject (the requested hashtag)
 *
 * Required request params:
 *   type       hashtag, username, location
 *   subject    depends on type param:
 *                  hashtag: string
 *                  username: string
 *                  location: instagram location id
 *
 *   rewrite    (optional) forces db rewrite for existing records
 *
 * @package instagram-scraper
 * @author bumbus <sf@arillo.net>
 */
class ScraperTask extends BuildTask
{
    /**
     * url param for type description
     * @var string
     */
    const URLPARAM_TYPE = 'type';

    /**
     * url param for subject description
     * @var string
     */
    const URLPARAM_SUBJECT = 'subject';

    /**
     * url param forcing a db rewrite
     * @var string
     */
    const URLPARAM_FORCE_REWRITE = 'rewrite';

    /**
     * @param  SS_HttpRequest $request
     */
    public function run($request)
    {
        $subject = $request->getVar(self::URLPARAM_SUBJECT);
        $type = $request->getVar(self::URLPARAM_TYPE);
        $allowedTypes = array_keys(
            InstagramRecord::feed_types()
        );

        if (!isset($type) || !isset($subject)) die('Insufficiant params given');
        if (!in_array($type, $allowedTypes)) die('Unsupported type requested.');

        $crawler = new Crawler();
        $data = null;
        $existingRecords = null;
        $added = 0;
        $updated = 0;
        $skipped = 0;

        switch ($type)
        {
            case InstagramRecord::TYPE_HASHTAG:
                try {
                    $responseBody = $crawler->getByTag($subject);
                    $data = array_column($responseBody['graphql']['hashtag']['edge_hashtag_to_media']['edges'], 'node');
                } catch (GuzzleException $e) {
                    die($e->getMessage());
                }
                break;

            case InstagramRecord::TYPE_USERNAME:
                try {
                    $responseBody = $crawler->getByUsername($subject);
                    $data = $responseBody['user']['media']['nodes'];
                } catch (GuzzleException $e) {
                    die($e->getMessage());
                }
                break;

            case InstagramRecord::TYPE_LOCATION:
                try {
                    $responseBody = $crawler->getByLocation($subject);
                    $data = $responseBody['location']['media']['nodes'];
                } catch (GuzzleException $e) {
                    die($e->getMessage());
                }
                break;
        }

        if ($data)
        {
            $externalIds = array_map(function($i) {
                if (isset($i['id'])) return $i['id'];
            }, $data);

            $existingRecords = InstagramRecord::get()->filter([
                'ExternalId' => $externalIds,
                'FeedType' => $type,
                'FeedSubject' => $subject,
            ]);

            foreach ($data as $instagramEntry)
            {
                $instagramRecord = null;
                $json = json_encode($instagramEntry);

                if ($rec = $existingRecords->find('ExternalId', $instagramEntry['id']))
                {
                    if ($json == $rec->Json && !$request->getVar(self::URLPARAM_FORCE_REWRITE))
                    {
                        $skipped++;
                        continue;
                    }

                    $instagramRecord = $rec;
                    $updated++;
                }

                if (!$instagramRecord)
                {
                    $instagramRecord = InstagramRecord::create();
                    $added++;
                }

                $takenAt = null;

                switch (true)
                {
                    case isset($instagramEntry['taken_at_timestamp']):
                        $takenAt = $instagramEntry['taken_at_timestamp'];
                        break;

                    case isset($instagramEntry['date']):
                        $takenAt = $instagramEntry['date'];
                        break;
                }

                $instagramRecord->update([
                    'ExternalId' => $instagramEntry['id'],
                    'TakenAtTimestamp' => $takenAt ? $takenAt : 'No date found',
                    'FeedType' => $type,
                    'FeedSubject' => $subject,
                    'Json' => $json,
                ]);

                $instagramRecord->write();
            }
        }
        die("Done: added[{$added}], skipped[{$skipped}], updated[{$updated}]");
    }
}
