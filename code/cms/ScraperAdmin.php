<?php
namespace Arillo\InstagramScraper\CMS;

use \ModelAdmin;
use \GridFieldBulkManager;

/**
 * @package instagram-scraper
 * @author bumbus <sf@arillo.net>
 */
class ScraperAdmin extends ModelAdmin
{
    private static
        $managed_models = [
            'InstagramRecord',
        ],

        $url_segment = 'instagram-scraper',
        $menu_title = 'Instagram scraper'
    ;

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if ($this->modelClass === 'InstagramRecord')
        {
            $form
                ->Fields()
                ->dataFieldByName(
                    $this->sanitiseClassName($this->modelClass)
                )
                ->getConfig()
                ->removeComponentsByType('GridFieldExportButton')
                ->removeComponentsByType('GridFieldPrintButton')
                ->addComponent($s = (new GridFieldBulkManager())
                    ->removeBulkAction('unLink')
                    ->removeBulkAction('bulkEdit')
                    ->removeBulkAction('delete')
                    ->addBulkAction(
                        'activate',
                        'Activate',
                        'Arillo\InstagramScraper\CMS\InstagramRecordBulkHandler',
                         [ 'isAjax' => true, 'icon' => 'accept', 'isDestructive' => false ]
                    )
                    ->addBulkAction(
                        'deactivate',
                        'Deactivate',
                        'Arillo\InstagramScraper\CMS\InstagramRecordBulkHandler',
                         [ 'isAjax' => true, 'icon' => 'decline', 'isDestructive' => false ]
                    )
                )
            ;
        }
        return $form;
    }

    /**
     * Override: no imports here
     * @return bool
     */
    public function getModelImporters()
    {
        return false;
    }

    /**
     * Override: no imports here
     * @return bool
     */
    public function ImportForm()
    {
        return false;
    }

    /**
     * Override: no imports here
     * @return bool
     */
    public function import($data, $form, $request)
    {
        return false;
    }
}
