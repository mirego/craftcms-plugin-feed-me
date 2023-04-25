<?php

namespace craft\feedme\elements;

use Cake\Utility\Hash;
use Craft;
use craft\elements\Category as CategoryElement;
use craft\feedme\base\Element;
use craft\feedme\helpers\DataHelper;
use craft\feedme\Plugin;

/**
 *
 * @property-read string $mappingTemplate
 * @property-read mixed $groups
 * @property-write mixed $model
 * @property-read string $groupsTemplate
 * @property-read string $columnTemplate
 */
class Category extends Element
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static $name = 'Category';

    /**
     * @var string
     */
    public static $class = 'craft\elements\Category';


    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getGroupsTemplate()
    {
        return 'feed-me/_includes/elements/categories/groups';
    }

    /**
     * @inheritDoc
     */
    public function getColumnTemplate()
    {
        return 'feed-me/_includes/elements/categories/column';
    }

    /**
     * @inheritDoc
     */
    public function getMappingTemplate()
    {
        return 'feed-me/_includes/elements/categories/map';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getGroups()
    {
        return Craft::$app->categories->getEditableGroups();
    }

    /**
     * @inheritDoc
     */
    public function getQuery($settings, $params = [])
    {
        $query = CategoryElement::find()
            ->anyStatus()
            ->groupId($settings['elementGroup'][CategoryElement::class])
            ->siteId(Hash::get($settings, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id);
        Craft::configure($query, $params);
        return $query;
    }

    /**
     * @inheritDoc
     */
    public function setModel($settings)
    {
        $this->element = new CategoryElement();
        $this->element->groupId = $settings['elementGroup'][CategoryElement::class];

        $siteId = Hash::get($settings, 'siteId');

        if ($siteId) {
            $this->element->siteId = $siteId;
        }

        return $this->element;
    }

    /**
     * @inheritDoc
     */
    public function afterSave($data, $settings)
    {
        $parent = Hash::get($data, 'parent');

        if ($parent && $parent !== $this->element->id) {
            $parentCategory = CategoryElement::find()->status(null)->id($parent)->one();

            Craft::$app->getStructures()->append($this->element->group->structureId, $this->element, $parentCategory);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return int|null
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function parseParent($feedData, $fieldInfo)
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);
        $default = DataHelper::fetchDefaultArrayValue($fieldInfo);

        $match = Hash::get($fieldInfo, 'options.match');
        $create = Hash::get($fieldInfo, 'options.create');
        $node = Hash::get($fieldInfo, 'node');

        // Element lookups must have a value to match against
        if ($value === null || $value === '') {
            return null;
        }

        if ($node === 'usedefault') {
            $match = 'elements.id';
        }

        $query = CategoryElement::find()
            ->status(null)
            ->andWhere(['=', $match, $value]);

        if (isset($this->feed['siteId']) && $this->feed['siteId']) {
            $query->siteId($this->feed['siteId']);
        }

        // fix for https://github.com/craftcms/feed-me/issues/1154#issuecomment-1429622276
        if (!empty($this->element->groupId)) {
            $query->groupId($this->element->groupId);
        }

        $element = $query->one();

        if ($element) {
            return $element->id;
        }

        // Check if we should create the element. But only if title is provided (for the moment)
        if ($create && $match === 'title') {
            $element = new CategoryElement();
            $element->title = $value;
            $element->groupId = $this->element->groupId;

            if (!Craft::$app->getElements()->saveElement($element, true, true, Hash::get($this->feed, 'updateSearchIndexes'))) {
                Plugin::error('Category error: Could not create parent - `{e}`.', ['e' => json_encode($element->getErrors())]);
            } else {
                Plugin::info('Category `#{id}` added.', ['id' => $element->id]);
            }

            return $element->id;
        }
        
        // use the default value if it's provided and none of the above worked
        // https://github.com/craftcms/feed-me/issues/1154
        if (!empty($default)) {
            $this->element->newParentId = $default[0];

            return $default[0];
        }

        return null;
    }
}
