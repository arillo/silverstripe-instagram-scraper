<?php

use Arillo\InstagramScraper\Crawler;

/**
 * Data model to store cached instagram records, collected by the scraper.
 *
 * @package instagram-scraper
 * @author bumbus <sf@arillo.net>
 */
class InstagramRecord extends DataObject
{
    /**
     * hashtag type
     * @var string
     */
    const TYPE_HASHTAG = 'hashtag';

    /**
     * username type
     * @var string
     */
    const TYPE_USERNAME = 'username';

    /**
     * location type
     * @var string
     */
    const TYPE_LOCATION = 'location';

    /**
     * list of available image dimensions.
     * @var array
     */
    const IMAGE_DIMENSIONS = [ 150, 240, 320, 480, 640 ];

    private static
        $db = [
            'ExternalId' => 'Varchar(255)',
            'TakenAtTimestamp' => 'Varchar(20)',
            'FeedType' => 'Varchar(255)',
            'FeedSubject' => 'Varchar(255)',
            'Json' => 'JSONText',
            'Hidden' => 'Boolean',
        ],

        $indexes = [
            'ExternalIdIndex' => [
                'type' => 'unique',
                'value' => 'ExternalId',
            ],

            'FeedTypeSubject' => [
                'type' => 'index',
                'value' => '"FeedType", "FeedSubject"',
            ],
        ],

        $default_sort = 'TakenAtTimestamp DESC',

        $defaults = [
            'FeedSubject' => self::TYPE_HASHTAG,
        ],

        $summary_fields = [
            'Thumbnail' => 'Thumbnail',
            'Status' => 'Status',
            'Created' => 'Created',
            'LastEdited' => 'LastEdited',
            'FeedType' => 'FeedType',
            'FeedSubject' => 'FeedSubject',
            'TakenAtTimestamp' => 'TakenAtTimestamp',
            'ExternalId' => 'ExternalId',
        ],

        $searchable_fields = [
            'Hidden' => 'Hidden',
            'FeedType' => 'FeedType',
            'FeedSubject' => 'FeedSubject',
            'ExternalId' => 'ExternalId',
        ]
    ;

    protected
        /**
         * @var array
         */
        $images = [],

        /**
         * @var HTMLText
         */
        $text = null,

        /**
         * @var ArrayData
         */
        $postViewPage = null
    ;

    /**
     * Map of availabel feed types
     * @return array
     */
    public static function feed_types()
    {
        $types = [
            self::TYPE_HASHTAG,
            self::TYPE_USERNAME,
            self::TYPE_LOCATION,
        ];

        return array_combine($types, $types);
    }

    /**
     * Get records by subject and type.
     * Filter is optional, will override
     *
     * @param  string $subject  query
     * @param  string $type     one of @see self::feed_types()
     * @param  array $filter    filtering addition
     * @return DataList
     */
    public static function by_topic(
        string $subject,
        string $type = self::TYPE_HASHTAG,
        array $filter = []
    ) {
        return self::get()
            ->filter(array_merge(
                $filter,
                [
                    'FeedSubject' => $subject,
                    'FeedType' => $type,
                ]
            )
        );
    }

    /**
     * Text representation of current Status.
     * @return string
     */
    public function getStatus()
    {
        return $this->Hidden
            ? _t('InstagramRecord.Deactivated', 'deactivated')
            : _t('InstagramRecord.Active', 'active')
        ;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $jsonPretty = json_encode(
            json_decode($this->Json),
            JSON_PRETTY_PRINT
        );

        $fields->replaceField(
            'FeedType',
            DropdownField::create('FeedType', 'Feed type', self::feed_types())
        );

        $fields->replaceField(
            'Json',
            CodeEditorField::create('JsonPretty', 'Json')
                ->addExtraClass('stacked')
                ->setValue($jsonPretty)
                ->setRows(30)
                ->setMode('json')
        );
        return $fields;
    }

    /**
     * Get a thumbnail image wrapped in HTMLText for CMS use.
     * @return HTMLText
     */
    public function getThumbnail()
    {
        $txt = _t('InstagramRecord.NoImage', '(no image)');

        if ($img = $this->Image())
        {
            $txt = "<img src='{$img->URL}' style='width: 150px;' />";
        }

        return DBField::create_field('HTMLText', $txt);
    }

    /**
     * Get image by dimension.
     *
     * @param  integer $width   one of @see self::IMAGE_DIMENSIONS
     * @return ArrayData
     */
    public function Image($width = self::IMAGE_DIMENSIONS[0])
    {
        $defaultKey = 'display_url';

        if (!isset($this->images[$width]))
        {
            $resources = $this
                ->dbObject('Json')
                ->setReturnType('array')
                ->query('$.thumbnail_resources')
            ;

            // try to find requested image data in json.
            if (!empty($resources) && in_array($width, self::IMAGE_DIMENSIONS))
            {
                $resources = $resources[0];
                $foundResource = ArrayList::create($resources)
                    ->find('config_width', $width);

                if ($foundResource)
                {
                    $image = ArrayData::create([
                        'URL' => $foundResource['src'],
                        'Width' => $foundResource['config_width'],
                        'Height' => $foundResource['config_height'],
                    ]);

                    $this->images[$width] = $image;
                    return $image;
                }
            }

            // fallback to standard display image
            $url = $this
                ->dbObject('Json')
                ->setReturnType('silverstripe')
                ->query('$.display_url')
            ;

            $image = ArrayData::create([
                'URL' => empty($url) ? null : $url[0],
            ]);

            $this->images[$defaultKey] = $image;
            return $image;
        }

        return $this->images[$width];
    }

    /**
     * Wrap caption from json in HTMLText.
     *
     * @return HTMLText
     */
    public function getText()
    {
        if (!$this->text)
        {
            $txt = $this
                ->dbObject('Json')
                ->setReturnType('silverstripe')
                ->query('$.edge_media_to_caption.edges[*].node.text')
            ;

            $this->text = DBField::create_field(
                'HTMLText',
                (empty($txt) ? null : htmlspecialchars_decode($txt[0]))
            );
        }

        return $this->text;
    }

    /**
     * Get an image view (page) link.
     *
     * @return ArrayData
     */
    public function getPostViewPage()
    {
        if (!$this->postViewPage)
        {
            $shortCode = $this
                ->dbObject('Json')
                ->setReturnType('silverstripe')
                ->query('$.shortcode')
            ;

            $this->postViewPage = ArrayData::create([
                'Link' => empty($shortCode)
                    ? null
                    : Crawler::media_url_by_shortcode($shortCode[0]),
            ]);
        }

        return $this->postViewPage;
    }

    public function canCreate($member = null)
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return $this->canCreate($member);
    }

    public function canDelete($member = null)
    {
        return $this->canCreate($member);
    }
}
