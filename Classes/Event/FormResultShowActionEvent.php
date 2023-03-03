<?php

namespace Lavitto\FormToDatabase\Event;

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

final class FormResultShowActionEvent
{
    private string $formPersistenceIdentifier;
    private QueryResult $formResults;
    private FormDefinition $formDefinition;
    private array $formRenderables;

    /**
     * @param string $formPersistenceIdentifier
     * @param QueryResult $formResults
     * @param FormDefinition $formDefinition
     * @param array $formRenderables
     */
    public function __construct(string $formPersistenceIdentifier, QueryResult $formResults, FormDefinition $formDefinition, array $formRenderables)
    {
        $this->formPersistenceIdentifier = $formPersistenceIdentifier;
        $this->formResults = $formResults;
        $this->formDefinition = $formDefinition;
        $this->formRenderables = $formRenderables;
    }

    /**
     * @return string
     */
    public function getFormPersistenceIdentifier(): string
    {
        return $this->formPersistenceIdentifier;
    }

    /**
     * @return QueryResult
     */
    public function getFormResults(): QueryResult
    {
        return $this->formResults;
    }

    /**
     * @return FormDefinition
     */
    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * @return array
     */
    public function getFormRenderables(): array
    {
        return $this->formRenderables;
    }




}