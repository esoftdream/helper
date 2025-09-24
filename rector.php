<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\AnnotationWithValueToAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector;
use Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_81,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_100,
    ]);

    $rectorConfig->parallel();

    // Github action cache
    $rectorConfig->cacheClass(FileCacheStorage::class);
    if (is_dir('/tmp')) {
        $rectorConfig->cacheDirectory('/tmp/rector');
    }

    // The paths to refactor (can also be supplied with CLI arguments)
    $rectorConfig->paths([
        __DIR__ . '/src/',
    ]);

    // Include Composer's autoload - required for global execution, remove if running locally
    $rectorConfig->autoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ]);

    // Do you need to include constants, class aliases, or a custom autoloader?
    $rectorConfig->bootstrapFiles([
        realpath(getcwd()) . '/vendor/codeigniter4/framework/system/Test/bootstrap.php',
    ]);

    if (is_file(__DIR__ . '/phpstan.neon.dist')) {
        $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon.dist');
    }

    // Set the target version for refactoring
    $rectorConfig->phpVersion(PhpVersion::PHP_80);

    // Auto-import fully qualified class names
    $rectorConfig->importNames();

    $rectorConfig->skip([
        // __DIR__ . '/app/Config',
        // __DIR__ . '/app/Views',

        StringifyStrNeedlesRector::class,
        YieldDataProviderRector::class,

        // Note: requires php 8
        RemoveUnusedPromotedPropertyRector::class,
        AnnotationWithValueToAttributeRector::class,

        // May load view files directly when detecting classes
        StringClassNameToClassConstantRector::class,
    ]);

    $rectorConfig->rules([
        // CodeQuality
        SimplifyUselessVariableRector::class,
        CompleteDynamicPropertiesRector::class,
        SimplifyEmptyArrayCheckRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        InlineIfToExplicitIfRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        ChangeArrayPushToArrayAssignRector::class,
        SimplifyRegexPatternRector::class,
        SimplifyStrposLowerRector::class,
        SingleInArrayToCompareRector::class,
        CombineIfRector::class,
        ExplicitBoolCompareRector::class,
        ShortenElseIfRector::class,
        SimplifyIfElseToTernaryRector::class,
        SimplifyIfReturnBoolRector::class,
        TernaryEmptyArrayArrayDimFetchToCoalesceRector::class,
        UnnecessaryTernaryExpressionRector::class,

        // CodingStyle
        FuncGetArgsToVariadicParamRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        CountArrayToEmptyArrayComparisonRector::class,
        VersionCompareFuncCallToConstantRector::class,

        // EarlyReturn
        ChangeNestedForeachIfsToEarlyContinueRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        RemoveAlwaysElseRector::class,
        PreparedValueToEarlyReturnRector::class,

        // Strict
        BooleanInIfConditionRuleFixerRector::class,
        DisallowedEmptyRuleFixerRector::class,

        // TypeDeclaration
        EmptyOnNullableObjectToInstanceOfRector::class,

        // Privatization
        PrivatizeFinalClassPropertyRector::class,
    ]);
};
