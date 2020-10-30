<?php
namespace Psalm;

use Psalm\Plugin\Hook;
use Psalm\Plugin\RegistrationInterface;
use function class_exists;
use function is_subclass_of;

class PluginRegistrationSocket implements RegistrationInterface
{
    /** @var Config */
    private $config;

    /** @var Codebase */
    private $codebase;

    /**
     * @internal
     */
    public function __construct(Config $config, Codebase $codebase)
    {
        $this->config = $config;
        $this->codebase = $codebase;
    }

    public function addStubFile(string $file_name): void
    {
        $this->config->addStubFile($file_name);
    }

    public function registerHooksFromClass(string $handler): void
    {
        if (!class_exists($handler, false)) {
            throw new \InvalidArgumentException('Plugins must be loaded before registration');
        }

        if (is_subclass_of($handler, Hook\AfterFileAnalysisInterface::class)) {
            $this->config->after_file_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterMethodCallAnalysisInterface::class)) {
            $this->config->after_method_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterFunctionCallAnalysisInterface::class)) {
            $this->config->after_function_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterEveryFunctionCallAnalysisInterface::class)) {
            $this->config->after_every_function_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterExpressionAnalysisInterface::class)) {
            $this->config->after_expression_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterStatementAnalysisInterface::class)) {
            $this->config->after_statement_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterClassLikeExistenceCheckInterface::class)) {
            $this->config->after_classlike_exists_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterClassLikeAnalysisInterface::class)) {
            $this->config->after_classlike_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterClassLikeVisitInterface::class)) {
            $this->config->after_visit_classlikes[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterCodebasePopulatedInterface::class)) {
            $this->config->after_codebase_populated[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\BeforeFileAnalysisInterface::class)) {
            $this->config->before_file_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\PropertyExistenceProviderInterface::class)) {
            $this->codebase->properties->property_existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\PropertyVisibilityProviderInterface::class)) {
            $this->codebase->properties->property_visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\PropertyTypeProviderInterface::class)) {
            $this->codebase->properties->property_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodExistenceProviderInterface::class)) {
            $this->codebase->methods->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodVisibilityProviderInterface::class)) {
            $this->codebase->methods->visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodReturnTypeProviderInterface::class)) {
            $this->codebase->methods->return_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodParamsProviderInterface::class)) {
            $this->codebase->methods->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\FunctionExistenceProviderInterface::class)) {
            $this->codebase->functions->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\FunctionParamsProviderInterface::class)) {
            $this->codebase->functions->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\FunctionReturnTypeProviderInterface::class)) {
            $this->codebase->functions->return_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\AfterAnalysisInterface::class)) {
            $this->config->after_analysis[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\StringInterpreterInterface::class)) {
            $this->config->string_interpreters[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterFunctionLikeAnalysisInterface::class)) {
            $this->config->after_functionlike_checks[$handler] = $handler;
        }
    }
}
