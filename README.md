# SilverStripe Instagram Scraper

## Introduction

This module provides a scraper task to fetch recent media records by hashtag,
username and location. Records will are persisted in database (InstagramRecord).

## Requirements

* php ^7.0.0
* ext-json
* guzzlehttp/guzzle ^6.2
* phptek/jsontext ^1.0.1
* nathancox/codeeditorfield ^1.3
* colymba/gridfield-bulk-editing-tools 2.1.x-dev

## Installation

```
composer require arillo/silverstripe-instagram-scraper
```

## Usage

For fetching instagram records you can run
Arillo\InstagramScraper\Tasks\ScraperTask with the following parameters:

* type (hashtag, username or location)
* subject (the query for the given type)

You can run following tasks through the commandline (e.g. with cronjobs).

### Query by hashtag

```
php framework/cli-script.php Arillo\InstagramScraper\Tasks\ScraperTask type=hashtag subject=<the_hashtag_to_query>
```

### Query by username

```
php framework/cli-script.php Arillo\InstagramScraper\Tasks\ScraperTask type=username subject=<the_username_to_query>
```

### Query by location

```
php framework/cli-script.php Arillo\InstagramScraper\Tasks\ScraperTask type=location subject=<instagram_location_id>
```

Or you can run them via the dev/tasks section in your browser.

### Work with instagram records

```
// get records by topic (subject & type)
$records = InstagramRecord::by_topic(<subject>, <type>);

// $records is a data list, the query can be modified...
$records
  ->exclude('Hidden', true)
  ->limit(10)
  ->sort('TakenAtTimestamp DESC')
;
```

## Notes

* There might be problems to fetch data from Instagram caused by rate limiting.
* Please note that Instagram might change access permissions to the API endpoint
  used by this module.

## Contribute

If you find a bug or you have feature request, please post an issue and/or send
a merge request.
