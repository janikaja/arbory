<?php

namespace Arbory\Base\Admin\Filter;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Arbory\Base\Admin\Filter\Parameters\FilterParameters;
use Arbory\Base\Admin\Filter\Concerns\WithParameterValidation;

class FilterValidatorBuilder
{

    public const RULES_METHOD = 'rules';
    public const MESSAGES_METHOD = 'messages';
    public const ATTRIBUTES_METHOD = 'attributes';

    protected const VALIDATION_CONCERNS = [WithParameterValidation::class];

    /**
     * @var ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * FilterValidator constructor.
     * @param ValidatorFactory $validatorFactory
     */
    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param FilterCollection $filterCollection
     * @param FilterParameters $filterParameters
     *
     * @return bool
     */
    public function validate(FilterCollection $filterCollection, FilterParameters $filterParameters): bool
    {
        return $this->build($filterCollection, $filterParameters)->fails();
    }

    /**
     * @param FilterCollection $filterCollection
     * @param FilterParameters $filterParameters
     * @return Validator
     */
    public function build(FilterCollection $filterCollection, FilterParameters $filterParameters): Validator
    {
        $validationObject = new FilterValidationObject();

        foreach ($filterCollection->findByConcerns(self::VALIDATION_CONCERNS) as $filterItem) {
            $type = $filterItem->getType();

            $validationObject->addRule($this->buildRules($filterItem, $filterParameters));
            $validationObject->addMessage($this->buildMessages($filterItem, $filterParameters));
            $validationObject->addAttribute($this->buildAttributes($filterItem, $filterParameters));

            if (method_exists($type, 'withValidator')) {
                $validationObject->addTransformer([
                    Closure::fromCallable([$type, 'withValidator']),
                    $this->getAttributeResolver($filterItem)
                ]);
            }
        }

        $validator = $this->validatorFactory->make(
            $filterParameters->toArray(), ...$validationObject->getAllForValidator()
        );

        $this->applyTransformers($validationObject->getTranformers(), $validator, $filterParameters);

        return $validator;
    }

    /**
     * @param array $transformers
     * @param $validator
     * @param FilterParameters $filterParameters
     */
    protected function applyTransformers(array $transformers, $validator, FilterParameters $filterParameters): void
    {
        foreach ($transformers as $transformerData) {
            $transformer = $transformerData[0];

            $transformer($validator, $filterParameters, $transformerData[1]);
        }
    }

    /**
     * @param FilterItem $filterItem
     * @param FilterParameters $filterParameters
     * @return array
     */
    public function buildRules(FilterItem $filterItem, FilterParameters $filterParameters): array
    {
        return $this->normalize($filterItem, $this->resolveMethod('rules', $filterItem, $filterParameters));
    }

    /**
     * @param FilterItem $filterItem
     * @param FilterParameters $filterParameters
     * @return array
     */
    public function buildMessages(FilterItem $filterItem, FilterParameters $filterParameters): array
    {
        return $this->normalize($filterItem, $this->resolveMethod('messages', $filterItem, $filterParameters), false);
    }

    /**
     * @param FilterItem $filterItem
     * @param FilterParameters $filterParameters
     * @return array
     */
    public function buildAttributes(FilterItem $filterItem, FilterParameters $filterParameters): array
    {
        return $this->normalize($filterItem, $this->resolveMethod('attributes', $filterItem, $filterParameters), false);
    }

    /**
     * @param FilterItem $filterItem
     * @param array $data
     * @param bool $prependName
     *
     * @return array
     */
    protected function normalize(FilterItem $filterItem, array $data, bool $prependName = true): array
    {
        // A single field with rules
        if (! Arr::isAssoc($data)) {
            return [
                $filterItem->getName() => $data,
            ];
        }

        $normalized = [];

        foreach ($data as $field => $ruleList) {
            $name = $field;

            if ($prependName) {
                $name = "{$filterItem->getName()}.{$name}";
            }

            $normalized[$name] = $ruleList;
        }

        return $normalized;
    }

    /**
     * @param string $method
     * @param FilterItem $filterItem
     * @param FilterParameters $filterParameters
     * @return mixed
     */
    protected function resolveMethod(string $method, FilterItem $filterItem, FilterParameters $filterParameters)
    {
        /**
         * @var WithParameterValidation
         */
        $type = $filterItem->getType();

        return $type->{$method}($filterParameters, $this->getAttributeResolver($filterItem));
    }

    /**
     * @param FilterItem $filterItem
     * @return callable
     */
    protected function getAttributeResolver(FilterItem $filterItem): callable
    {
        return static function (?string $attribute = null) use ($filterItem) {
            return $filterItem->getName().($attribute ? '.'.$attribute : '');
        };
    }
}
