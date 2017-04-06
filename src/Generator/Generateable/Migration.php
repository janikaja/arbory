<?php

namespace CubeSystems\Leaf\Generator\Generateable;

use CubeSystems\Leaf\Generator\Generateable\Extras\Field;
use CubeSystems\Leaf\Generator\GeneratorFormatter;
use CubeSystems\Leaf\Generator\Stubable;
use CubeSystems\Leaf\Generator\StubGenerator;
use CubeSystems\Leaf\Services\StubRegistry;
use DateTimeImmutable;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;

class Migration extends StubGenerator implements Stubable
{
    use GeneratorFormatter, DetectsApplicationNamespace;

    /**
     * @param StubRegistry $stubRegistry
     * @param Filesystem $filesystem
     * @param Model $model
     */
    public function __construct(
        StubRegistry $stubRegistry,
        Filesystem $filesystem,
        Model $model
    )
    {
        $this->stub = $stubRegistry->findByName( 'controller' );
        $this->filesystem = $filesystem;
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getCompiledControllerStub(): string
    {
        $schemaFields = ( clone $this->model->getFields() )->transform( function( $field )
        {
            /**
             * @var Field $field
             */
            return sprintf(
                '$table->%s(\'%s\');',
                $field->getStructure()->getType(),
                $field->getDatabaseName()
            );
        } );

        $replace = [
            '{{className}}' => $this->getClassName(),
            '{{schemaName}}' => $this->model->getDatabaseName(),
            '{{schemaFields}}' => $this->prependSpacing( $schemaFields,3 )->implode( PHP_EOL ),
            '{{downAction}}' => 'Schema::dropIfExists( \'' . $this->model->getDatabaseName() . '\' );'
        ];

        return str_replace(
            array_keys( $replace ),
            array_values( $replace ),
            $this->stub->getContents()
        );
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return 'Create' . $this->model->getClassName() . 'Table';
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        $time = new DateTimeImmutable();

        return sprintf(
            '%s_create_%s_table.php',
            $time->format( 'Y_m_d_His' ),
            snake_case( $this->model->getName() )
        );
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return (string) null;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return base_path( 'database/migrations/' . $this->getFilename() );
    }
}