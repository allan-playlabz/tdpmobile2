<?php

namespace App\Lib;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Translator class
 * Library class for getting translation of attraction or ticket
 */
class Translator
{

    public function __construct()
    {
        
    }

    /**
     * Translate attraction if languare query is present
     *
     * @param Cake\Http\Client\Request $request
     * @param App\Model\Entity\Attraction $attraction
     * @return App\Model\Entity\Ticket $ticket
     */
    public static function translateAttraction($request, $attraction) {
        $AttractionTranslations = TableRegistry::getTableLocator()->get('AttractionTranslations');
        if ($request->getSession()->check('language')) {
            $language = $request->getSession()->read('language');
            $languages = Configure::read('Translation.languages');
            if (in_array($language, array_keys($languages))) {
                $attraction_translation = $AttractionTranslations->findTranslation($attraction->id, $language);
                if ($attraction_translation) {
                    $attraction->title = $attraction_translation->title;
                    $attraction->description = $attraction_translation->description;
                    $attraction->terms_and_conditions = $attraction_translation->terms_and_conditions;
                }
            }
            
        }
        return $attraction;
    }

    /**
     * Translate ticket if languare query is present
     *
     * @param Cake\Http\Client\Request $request
     * @param App\Model\Entity\Ticket $ticket
     * @return App\Model\Entity\Ticket $ticket
     */
    public static function translateTicket($request, $ticket) {
        $TicketTranslations = TableRegistry::getTableLocator()->get('TicketTranslations');
        if ($request->getSession()->check('language')) {
            $language = $request->getSession()->read('language');
            $languages = Configure::read('Translation.languages');
            if (in_array($language, array_keys($languages))) {
                $ticket_translation = $TicketTranslations->findTranslation($ticket->id, $language);
                if ($ticket_translation) {
                    $ticket->name = $ticket_translation->title;
                    $ticket->description = $ticket_translation->description;
                    $ticket->terms_and_conditions = $ticket_translation->terms_and_conditions;
                }
                if ($ticket->has('attraction')) {
                    $ticket->attraction = self::translateAttraction($request, $ticket->attraction);
                }
            }
        }
        return $ticket;
    }

    /**
     * Translate category if languare query is present
     *
     * @param Cake\Http\Client\Request $request
     * @param App\Model\Entity\Category $category
     * @return App\Model\Entity\Category $category
     */
    public static function translateCategory($request, $category) {
        $CategoryTranslations = TableRegistry::getTableLocator()->get('CategoryTranslations');
        if ($request->getSession()->check('language')) {
            $language = $request->getSession()->read('language');
            $languages = Configure::read('Translation.languages');
            if (in_array($language, array_keys($languages))) {
                $category_translation = $CategoryTranslations->findTranslation($category->id, $language);
                if ($category_translation) {
                    $category->name = $category_translation->name;
                    if (!empty($category->child_categories)) {
                        foreach ($category->child_categories as $child_category) {
                            self::translateCategory($request, $child_category);
                        }
                    }
                }
            }
        }
        return $category;
    }
}
