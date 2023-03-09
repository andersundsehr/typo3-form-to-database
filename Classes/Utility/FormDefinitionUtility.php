<?php /** @noinspection PhpInternalEntityUsedInspection */

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;

/**
 * Class FormDefinitionUtility
 *
 * @package Lavitto\FormToDatabase\Utility
 */
class FormDefinitionUtility
{

    const fieldAttributeFilterKeys = ['identifier', 'label', 'type'];

    /**
     * @param array $formDefinition
     * @param bool $enableAllInListView
     * @param bool $force
     */
    public static function addFieldStateIfDoesNotExist(
        array &$formDefinition,
        bool  $enableAllInListView = false,
        bool  $force = false
    ): void {
        $fieldState = $formDefinition['renderingOptions']['fieldState'] ?? [];

        // If no state exists - create state from current fields
        if (empty($fieldState) || $force === true) {
            $newFieldState = self::addFieldsToStateFromFormDefinition(
                self::convertFormDefinitionToObject($formDefinition),
                $fieldState
            );

            $fieldCount = 0;
            //Mark all fields in state as not deleted
            $newFieldState = array_map(function ($field) use (&$fieldCount, $enableAllInListView) {
                $fieldCount++;
                if(!isset($field['renderingOptions']['deleted'])) {
                    $field['renderingOptions']['deleted'] = 0;
                }
                return $field;
            }, $newFieldState);

            // Clean up fieldState - remove if incomplete
            $newFieldState = array_filter($newFieldState, function($field) {
                return
                    !self::isCompositeElement($field) &&
                    count(array_intersect_key(array_flip(self::fieldAttributeFilterKeys,), $field)) === count(self::fieldAttributeFilterKeys);
            });


            $formDefinition['renderingOptions']['fieldState'] = $newFieldState;
        }
    }

    /**
     * @param FormDefinition $formDefinition
     * @param array $fieldState
     * @return array
     */
    protected static function addFieldsToStateFromFormDefinition(FormDefinition $formDefinition, array $fieldState = []): array
    {
        foreach ($formDefinition->getRenderablesRecursively() as $renderable) {
            if ($renderable instanceof \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface) {
                // Prevent composite elements within field state to avoid
                // duplication errors within form definition build
                continue;
            }
            self::addFieldToState($fieldState,
                ['identifier' => $renderable->getIdentifier(),
                    'label' => $renderable->getLabel(),
                    'type' => $renderable->getType(),
                    'renderingOptions' => ['deleted' => 0]
                ]);
        }
        return $fieldState;
    }

    /**
     * @param $field
     * @param $fieldState
     */
    public static function addFieldToState(&$fieldState, $field): void
    {
        $field = array_intersect_key($field, array_flip(self::fieldAttributeFilterKeys));
        ArrayUtility::mergeRecursiveWithOverrule($fieldState, [$field['identifier'] => $field]);
    }

    /**
     * @param array $formDefinition
     * @return FormDefinition
     */
    public static function convertFormDefinitionToObject(array $formDefinition): FormDefinition
    {

        /** @var ArrayFormFactory $arrayFormFactory */
        $arrayFormFactory = GeneralUtility::makeInstance(ArrayFormFactory::class);
        return $arrayFormFactory->build($formDefinition);
    }


    /**
     * @param $field
     * @return bool
     * @throws PrototypeNotFoundException
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     * @throws \TYPO3\CMS\Form\Exception
     */
    public static function isCompositeElement($field): bool
    {
        static $page;
        static $registeredElements = [];
        if(!isset($page)) {
            $prototypeConfiguration = GeneralUtility::makeInstance(ConfigurationService::class)
                ->getPrototypeConfiguration('standard');

            $formDef = GeneralUtility::makeInstance(
                FormDefinition::class,
                'fieldStageForm',
                $prototypeConfiguration,
                'Form'
            );

            $page = GeneralUtility::makeInstance(Page::class, 'fieldStatePage', 'Page');
            $page->setParentRenderable($formDef);
        }
        if(!isset($registeredElements[$field['identifier']])) {
            $element = $page->createElement($field['identifier'], $field['type']);
            $registeredElements[$field['identifier']] = $element instanceof \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
        }
        return $registeredElements[$field['identifier']];
    }
}