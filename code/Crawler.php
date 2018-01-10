<?php

namespace Arillo\InstagramScraper;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * This class provides an access api to public Instagram data.
 * Partly borrowed from Smochin\Instagram\Crawler.
 *
 * @package instagram-scraper
 * @author bumbus <sf@arillo.net>
 */
class Crawler
{
    const BASE_URI = 'https://www.instagram.com';
    const QUERY = ['__a' => 1];
    const TAG_ENDPOINT = '/explore/tags/%s';
    const LOCATION_ENDPOINT = '/explore/locations/%d';
    const USER_ENDPOINT = '/%s';
    const MEDIA_ENDPOINT = '/p/%s';

    /**
     * @var GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Returns a media url by shortcode for in-browser usage.
     *
     * @param  string $shortCode
     * @return string
     */
    public static function media_url_by_shortcode(string $shortCode): string
    {
        return self::BASE_URI . sprintf(self::MEDIA_ENDPOINT, $shortCode);
    }

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'query' => self::QUERY,
        ]);
    }

    /**
     * Get info by tag
     *
     * @param string $name The name of the hashtag
     * @return array response body
     * @throws GuzzleException
     */
    public function getByTag(string $name): array
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        return json_decode($response->getBody()->getContents(), true);
    }

      /**
     * Get info by username
     *
     * @param string $username
     * @return array response body
     * @throws GuzzleException
     */
    public function getByUsername(string $username): array
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get info by location (id)
     *
     * @param int $id Identification of the location
     * @return array response body
     * @throws GuzzleException
     */
    public function getByLocation(int $id): array
    {
        $response = $this->client->request('GET', sprintf(self::LOCATION_ENDPOINT, $id));
        return json_decode($response->getBody()->getContents(), true);
    }
}
