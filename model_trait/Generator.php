<?php
/**
 * @link http://neamlabs.com/
 * @copyright Copyright (c) 2015 Neam AB
 */

namespace neam\yii_content_model_metadata_generators\model_trait;

use Yii;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\Json;

/**
 * This generator will generate one or multiple model metadata traits for the specified item type(s).
 *
 * @author Fredrik Wollsén <fredrik@neam.se>
 * @since 1.0
 */
class Generator extends \neam\yii_content_model_metadata_generators\ContentModelMetadataGenerator
{

    public $ns = 'app\models\metadata\traits';

    /**
     * @var null string
     */
    public $itemType = '*';

    /**
     * @inheritdoc
     */
    public $templates = [
        'yii' => '@vendor/neam/yii2-content-model-metadata-generators/model_trait/yii',
        'yii2' => '@vendor/neam/yii2-content-model-metadata-generators/model_trait/yii2',
    ];

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Content Model Metadata Model Trait Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator will generate one or multiple model metadata traits for the specified item type(s).';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['itemType'], 'safe'],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'itemType' => 'Item Type(s)',
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(
            parent::hints(),
            [
                'itemType' => 'This is the name of the item type that the new trait is associated with, e.g. <code>post</code>.
                The item type may end with asterisk to match multiple item types, e.g. <code>foo*</code>
                will match item types whose name starts with <code>foo</code>. In this case, multiple traits
                will be generated, one for each matching item type.',
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['trait.php'];
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $files = [];

        foreach ($this->getMatchingItemTypes() as $itemType) {

            $traitName = $itemType->model_class . 'Trait';

            $params = [
                'itemType' => $itemType,
                'traitName' => $traitName,
                'ns' => $this->ns,
                'statusRequirements' => $this->generateStatusRequirements($itemType),
                'flowSteps' => $this->generateFlowSteps($itemType),
                'flowStepCaptions' => $this->generateFlowStepCaptions($itemType),
                'labels' => $this->generateLabels($itemType),
                'hints' => $this->generateHints($itemType),
            ];

            $modelTraitFile = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $traitName . '.php';
            $files[] = new CodeFile(
                $modelTraitFile,
                $this->render('trait.php', $params)
            );

        }

        return $files;
    }

    protected $itemTypes;

    /**
     * @return array the item types that match the pattern specified by [[itemType]].
     */
    protected function getMatchingItemTypes()
    {
        if ($this->itemTypes !== null) {
            return $this->itemTypes;
        }
        $cmm = $this->getContentModelMetadata();
        if ($cmm === null) {
            return [];
        }
        $itemTypes = [];

        $pattern = '/^' . str_replace('*', '\w+', $this->itemType) . '$/';
        foreach ($cmm->itemTypes as $itemType) {
            if (preg_match($pattern, $itemType->model_class)) {
                $itemTypes[] = $itemType;
            }
        }

        return $this->itemTypes = $itemTypes;
    }

    public function generateStatusRequirements($itemType)
    {
        $statusRequirements = [];
        foreach ($itemType->attributes as $attribute) {
            if (empty($attribute->preparableStatusRequirement)) {
                continue;
            }
            $statusRequirements[$attribute->preparableStatusRequirement->ref][] = $attribute->ref;
        }
        return $statusRequirements;
    }

    public function generateFlowSteps($itemType)
    {
        $flowSteps = [];
        foreach ($itemType->attributes as $attribute) {
            if (empty($attribute->workflowItemStep)) {
                continue;
            }
            $flowSteps[$attribute->workflowItemStep->ref][] = $attribute->ref;
        }
        return $flowSteps;
    }

    public function generateFlowStepCaptions($itemType)
    {
        $flowStepCaptions = [];
        foreach ($itemType->attributes as $attribute) {
            if (empty($attribute->workflowItemStep)) {
                continue;
            }
            $flowStepCaptions[$attribute->workflowItemStep->ref] = $attribute->workflowItemStep->_title;
        }
        return $flowStepCaptions;
    }

    /**
     * Generates the attribute labels for the specified item type.
     * @param stdCass $itemType the item type metadata
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($itemType)
    {
        $labels = [];
        foreach ($itemType->attributes as $attribute) {
            if (empty($attribute->label)) {
                continue;
            }
            $labels[$attribute->ref] = $attribute->label;
        }
        return $labels;
    }

    /**
     * Generates the attribute labels for the specified item type.
     * @param stdCass $itemType the item type metadata
     * @return array the generated attribute labels (name => label)
     */
    public function generateHints($itemType)
    {
        $labels = [];
        foreach ($itemType->attributes as $attribute) {
            if (empty($attribute->hint)) {
                continue;
            }
            $labels[$attribute->ref] = $attribute->hint;
        }
        return $labels;
    }

}
