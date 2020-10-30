<?php
namespace Psalm\Internal\Provider;

/**
 * @internal
 */
class Providers
{
    /**
     * @var FileProvider
     */
    public $file_provider;

    /**
     * @var ?ParserCacheProvider
     */
    public $parser_cache_provider;

    /**
     * @var FileStorageProvider
     */
    public $file_storage_provider;

    /**
     * @var ClassLikeStorageProvider
     */
    public $classlike_storage_provider;

    /**
     * @var StatementsProvider
     */
    public $statements_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var ?ProjectCacheProvider
     */
    public $project_cache_provider;

    public function __construct(
        FileProvider $file_provider,
        ?ParserCacheProvider $parser_cache_provider = null,
        ?FileStorageCacheProvider $file_storage_cache_provider = null,
        ?ClassLikeStorageCacheProvider $classlike_storage_cache_provider = null,
        ?FileReferenceCacheProvider $file_reference_cache_provider = null,
        ?ProjectCacheProvider $project_cache_provider = null
    ) {
        $this->file_provider = $file_provider;
        $this->parser_cache_provider = $parser_cache_provider;
        $this->project_cache_provider = $project_cache_provider;

        $this->file_storage_provider = new FileStorageProvider($file_storage_cache_provider);
        $this->classlike_storage_provider = new ClassLikeStorageProvider($classlike_storage_cache_provider);
        $this->statements_provider = new StatementsProvider(
            $file_provider,
            $parser_cache_provider,
            $file_storage_cache_provider
        );
        $this->file_reference_provider = new FileReferenceProvider($file_reference_cache_provider);
    }
}
