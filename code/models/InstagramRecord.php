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
         * @var ArrayData
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
     * Get records by subject and type
     * @param  string $subject
     * @param  string $type
     * @return DataList
     */
    public static function by_topic(string $subject, string $type = 'hashtag'): DataList
    {
        return self::get()
            ->filter([
                'FeedSubject' => $subject,
                'FeedType' => $type,
            ])
        ;
    }

    public function getStatus()
    {
        return $this->Hidden ? 'deactivated' : 'active';
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

    public function getThumbnail(): HTMLText
    {
        if ($img = $this->Image())
        {
            return DBField::create_field(
                'HTMLText',
                "<img src='{$img->URL}' style='width: 150px;' />"
            );
        }

        return '(no image)';

    }

    /**
     * Get image by dimension.
     *
     * @param  integer $width 150, 240, 320, 480, 640
     * @return ArrayData
     */
    public function Image($width = 150): ArrayData
    {
        $defaultKey = 'display_url';

        if (!isset($this->images[$width]))
        {
            $resources = $this
                ->dbObject('Json')
                ->setReturnType('array')
                ->query('$.thumbnail_resources')
            ;

            if (!empty($resources))
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

    public function getText(): HTMLText
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

    public function getPostViewPage(): ArrayData
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
